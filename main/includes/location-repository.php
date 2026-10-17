<?php
declare(strict_types=1);

/**
 * Location hierarchy helpers used by the "Add Location" manager.
 *
 * The four levels map onto the existing tables (no schema change required):
 *   province  -> province
 *   regency   -> district_city   (Kabupaten / Kota)
 *   district  -> sub_district    (Kecamatan)
 *   village   -> village         (Desa / Kelurahan)
 *
 * Regency type (Kabupaten vs Kota) is kept in the name prefix ("KAB. " / "KOTA "),
 * the same convention the BPS import uses. Village type (Desa vs Kelurahan) is
 * read from a `type` column when it exists (see database/add_location_types.php),
 * otherwise from the BPS code, otherwise it defaults to Desa.
 */

require_once __DIR__ . '/../../database/db.php';

const LOCATION_LEVELS = [
    'province' => [
        'table' => 'province',
        'parentColumn' => null,
        'parentLevel' => null,
        'childTable' => 'district_city',
        'childColumn' => 'province_id',
        'label' => 'province',
        'childLabel' => 'regencies / cities',
    ],
    'regency' => [
        'table' => 'district_city',
        'parentColumn' => 'province_id',
        'parentLevel' => 'province',
        'childTable' => 'sub_district',
        'childColumn' => 'district_id',
        'label' => 'regency / city',
        'childLabel' => 'districts',
    ],
    'district' => [
        'table' => 'sub_district',
        'parentColumn' => 'district_id',
        'parentLevel' => 'regency',
        'childTable' => 'village',
        'childColumn' => 'sub_district_id',
        'label' => 'district',
        'childLabel' => 'villages',
    ],
    'village' => [
        'table' => 'village',
        'parentColumn' => 'sub_district_id',
        'parentLevel' => 'district',
        'childTable' => 'wells',
        'childColumn' => 'village_id',
        'label' => 'village',
        'childLabel' => 'registered wells',
    ],
];

const LOCATION_TYPES = [
    'regency' => ['regency', 'city'],
    'village' => ['desa', 'kelurahan'],
];

function locationTableHasColumn(PDO $pdo, string $table, string $column): bool
{
    static $cache = [];
    $key = $table . '.' . $column;
    if (!array_key_exists($key, $cache)) {
        $columns = $pdo->query('PRAGMA table_info(' . $table . ')')->fetchAll();
        $cache[$key] = in_array($column, array_column($columns, 'name'), true);
    }
    return $cache[$key];
}

/** Collapse whitespace and trim a user-entered name. */
function locationCleanName(string $name): string
{
    return trim((string) preg_replace('/\s+/u', ' ', $name));
}

/** "BADUNG" -> "Badung", "nusa tenggara barat" -> "Nusa Tenggara Barat". */
function locationTitleCase(string $name): string
{
    return ucwords(strtolower($name), " \t-/(");
}

/** Split a stored regency name into [type, plain name]. */
function locationSplitRegencyName(string $rawName): array
{
    if (preg_match('/^\s*KOTA\.?\s+(.+)$/iu', $rawName, $match)) {
        return ['city', trim($match[1])];
    }
    if (preg_match('/^\s*(KAB\.?|KABUPATEN)\s+(.+)$/iu', $rawName, $match)) {
        return ['regency', trim($match[2])];
    }
    return ['regency', trim($rawName)];
}

/** Build the stored regency name, matching the BPS import ("KAB. BADUNG"). */
function locationRegencyStoredName(string $type, string $name): string
{
    $prefix = $type === 'city' ? 'KOTA ' : 'KAB. ';
    return $prefix . strtoupper($name);
}

/** BPS village codes carry the type in the 7th digit: 1 = Kelurahan, 2 = Desa. */
function locationVillageTypeFromCode(?string $code): ?string
{
    if ($code === null || strlen($code) < 7) {
        return null;
    }
    return match ($code[6]) {
        '1' => 'kelurahan',
        '2' => 'desa',
        default => null,
    };
}

/** Name used for display and duplicate checks. */
function locationDisplayName(string $level, string $rawName): string
{
    return match ($level) {
        'province' => locationTitleCase($rawName),
        'regency' => locationTitleCase(locationSplitRegencyName($rawName)[1]),
        default => $rawName,
    };
}

function locationRowType(PDO $pdo, string $level, array $row): ?string
{
    if ($level === 'regency') {
        return locationSplitRegencyName((string) $row['name'])[0];
    }
    if ($level === 'village') {
        if (!empty($row['type']) && in_array($row['type'], LOCATION_TYPES['village'], true)) {
            return (string) $row['type'];
        }
        return locationVillageTypeFromCode($row['code'] ?? null) ?? 'desa';
    }
    return null;
}

/**
 * List one level of the hierarchy, with each row's child count so the UI
 * can show it and explain why a delete is blocked.
 */
function locationList(PDO $pdo, string $level, ?int $parentId): array
{
    $config = LOCATION_LEVELS[$level];
    $table = $config['table'];
    $typeSelect = $level === 'village' && locationTableHasColumn($pdo, 'village', 'type') ? ', t.type' : '';

    $sql = "SELECT t.id, t.code, t.name{$typeSelect},
                   (SELECT COUNT(*) FROM {$config['childTable']} c WHERE c.{$config['childColumn']} = t.id) AS child_count
            FROM {$table} t";
    $params = [];
    if ($config['parentColumn'] !== null) {
        $sql .= " WHERE t.{$config['parentColumn']} = :parent";
        $params[':parent'] = $parentId;
    }

    $statement = $pdo->prepare($sql);
    $statement->execute($params);

    $items = array_map(static function (array $row) use ($pdo, $level): array {
        return [
            'id' => (int) $row['id'],
            'name' => locationDisplayName($level, (string) $row['name']),
            'type' => locationRowType($pdo, $level, $row),
            'childCount' => (int) $row['child_count'],
        ];
    }, $statement->fetchAll());

    usort($items, static fn (array $a, array $b): int => strcasecmp($a['name'], $b['name']));
    return $items;
}

function locationFind(PDO $pdo, string $level, int $id): ?array
{
    $config = LOCATION_LEVELS[$level];
    $statement = $pdo->prepare("SELECT * FROM {$config['table']} WHERE id = :id");
    $statement->execute([':id' => $id]);
    $row = $statement->fetch();
    return $row === false ? null : $row;
}

/** True when another row under the same parent already uses this name (and type). */
function locationIsDuplicate(PDO $pdo, string $level, ?int $parentId, string $name, ?string $type, ?int $ignoreId): bool
{
    foreach (locationList($pdo, $level, $parentId) as $item) {
        if ($ignoreId !== null && $item['id'] === $ignoreId) {
            continue;
        }
        $sameName = strcasecmp($item['name'], $name) === 0;
        $sameType = $level !== 'regency' || $item['type'] === $type;
        if ($sameName && $sameType) {
            return true;
        }
    }
    return false;
}

function locationStoredName(string $level, string $name, ?string $type): string
{
    return match ($level) {
        'province' => strtoupper($name),
        'regency' => locationRegencyStoredName($type ?? 'regency', $name),
        default => $name,
    };
}

function locationCreate(PDO $pdo, string $level, ?int $parentId, string $name, ?string $type): int
{
    $config = LOCATION_LEVELS[$level];
    $columns = ['name', 'status'];
    $values = [':name' => locationStoredName($level, $name, $type), ':status' => 1];

    if ($config['parentColumn'] !== null) {
        $columns[] = $config['parentColumn'];
        $values[':' . $config['parentColumn']] = $parentId;
    }
    if ($level === 'village' && locationTableHasColumn($pdo, 'village', 'type')) {
        $columns[] = 'type';
        $values[':type'] = $type;
    }

    $placeholders = array_map(static fn (string $column): string => ':' . $column, $columns);
    $statement = $pdo->prepare(sprintf(
        'INSERT INTO %s (%s) VALUES (%s)',
        $config['table'],
        implode(', ', $columns),
        implode(', ', $placeholders)
    ));
    $statement->execute($values);
    return (int) $pdo->lastInsertId();
}

function locationUpdate(PDO $pdo, string $level, int $id, string $name, ?string $type): void
{
    $config = LOCATION_LEVELS[$level];
    $sets = ['name = :name', 'updated_at = CURRENT_TIMESTAMP'];
    $values = [':name' => locationStoredName($level, $name, $type), ':id' => $id];

    if ($level === 'village' && locationTableHasColumn($pdo, 'village', 'type')) {
        $sets[] = 'type = :type';
        $values[':type'] = $type;
    }

    $statement = $pdo->prepare(sprintf('UPDATE %s SET %s WHERE id = :id', $config['table'], implode(', ', $sets)));
    $statement->execute($values);
}

function locationDelete(PDO $pdo, string $level, int $id): void
{
    $config = LOCATION_LEVELS[$level];
    $statement = $pdo->prepare("DELETE FROM {$config['table']} WHERE id = :id");
    $statement->execute([':id' => $id]);
}

function locationChildCount(PDO $pdo, string $level, int $id): int
{
    $config = LOCATION_LEVELS[$level];
    $statement = $pdo->prepare("SELECT COUNT(*) FROM {$config['childTable']} WHERE {$config['childColumn']} = :id");
    $statement->execute([':id' => $id]);
    return (int) $statement->fetchColumn();
}
