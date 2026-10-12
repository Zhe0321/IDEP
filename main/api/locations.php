<?php
declare(strict_types=1);

/**
 * Location manager API (admin / manager only).
 *
 *   GET  /main/api/locations.php?level=regency&parent_id=1
 *   POST /main/api/locations.php   JSON body:
 *        { "action": "create", "level": "village", "parentId": 12, "name": "Taro", "type": "desa" }
 *        { "action": "update", "level": "village", "id": 40, "name": "Taro", "type": "desa" }
 *        { "action": "delete", "level": "village", "id": 40 }
 */

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/includes/location-repository.php';

function locationRespond(int $status, array $body): never
{
    http_response_code($status);
    echo json_encode($body);
    exit;
}

session_start();
if (($_SESSION['idep_admin'] ?? false) !== true) {
    locationRespond(401, ['success' => false, 'message' => 'Your session has ended. Sign in again to manage locations.']);
}
session_write_close();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    $pdo = idepDatabase();
} catch (Throwable $error) {
    locationRespond(500, ['success' => false, 'message' => 'The location database is not available: ' . $error->getMessage()]);
}

if ($method === 'GET') {
    $level = (string) ($_GET['level'] ?? '');
    if (!array_key_exists($level, LOCATION_LEVELS)) {
        locationRespond(422, ['success' => false, 'message' => 'Unknown location level.']);
    }
    $parentId = LOCATION_LEVELS[$level]['parentColumn'] === null ? null : (int) ($_GET['parent_id'] ?? 0);

    locationRespond(200, [
        'success' => true,
        'items' => locationList($pdo, $level, $parentId),
        'villageTypeStored' => locationTableHasColumn($pdo, 'village', 'type'),
    ]);
}

if ($method !== 'POST') {
    locationRespond(405, ['success' => false, 'message' => 'Method not allowed.']);
}

// Only accept JSON sent by the dashboard script. Combined with the SameSite=Lax
// session cookie this stops other sites from submitting changes on a user's behalf.
$contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
if (!str_starts_with($contentType, 'application/json') || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'fetch') {
    locationRespond(400, ['success' => false, 'message' => 'Send location changes from the dashboard.']);
}

$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input)) {
    locationRespond(400, ['success' => false, 'message' => 'The request could not be read.']);
}

$action = (string) ($input['action'] ?? '');
$level = (string) ($input['level'] ?? '');
if (!array_key_exists($level, LOCATION_LEVELS)) {
    locationRespond(422, ['success' => false, 'message' => 'Unknown location level.']);
}
$config = LOCATION_LEVELS[$level];
$label = $config['label'];

try {
    if ($action === 'delete') {
        $id = (int) ($input['id'] ?? 0);
        $row = locationFind($pdo, $level, $id);
        if ($row === null) {
            locationRespond(404, ['success' => false, 'message' => "That {$label} no longer exists. Refresh the list."]);
        }
        $displayName = locationDisplayName($level, (string) $row['name']);
        $children = locationChildCount($pdo, $level, $id);
        if ($children > 0) {
            locationRespond(409, [
                'success' => false,
                'message' => "{$displayName} still has {$children} {$config['childLabel']}. Remove those first, then delete {$displayName}.",
            ]);
        }
        locationDelete($pdo, $level, $id);
        locationRespond(200, ['success' => true, 'message' => "{$displayName} deleted."]);
    }

    if ($action !== 'create' && $action !== 'update') {
        locationRespond(422, ['success' => false, 'message' => 'Unknown action.']);
    }

    $name = locationCleanName((string) ($input['name'] ?? ''));
    if ($name === '') {
        locationRespond(422, ['success' => false, 'message' => 'Enter a name.', 'field' => 'name']);
    }
    if (strlen($name) > 100) {
        locationRespond(422, ['success' => false, 'message' => 'Keep the name under 100 characters.', 'field' => 'name']);
    }
    // Store the plain name; the regency prefix is added from the type.
    if ($level === 'regency') {
        $name = locationSplitRegencyName($name)[1];
    }

    $type = null;
    if (isset(LOCATION_TYPES[$level])) {
        $type = (string) ($input['type'] ?? '');
        if (!in_array($type, LOCATION_TYPES[$level], true)) {
            locationRespond(422, ['success' => false, 'message' => 'Choose a type.', 'field' => 'type']);
        }
    }

    if ($action === 'create') {
        $parentId = null;
        if ($config['parentColumn'] !== null) {
            $parentId = (int) ($input['parentId'] ?? 0);
            $parentLevel = $config['parentLevel'];
            if ($parentId <= 0 || locationFind($pdo, $parentLevel, $parentId) === null) {
                $parentLabel = LOCATION_LEVELS[$parentLevel]['label'];
                locationRespond(422, ['success' => false, 'message' => "Choose a {$parentLabel} first.", 'field' => 'parent']);
            }
        }

        $displayName = $level === 'province' || $level === 'regency' ? locationTitleCase($name) : $name;
        if (locationIsDuplicate($pdo, $level, $parentId, $displayName, $type, null)) {
            locationRespond(409, ['success' => false, 'message' => "{$displayName} is already in the list.", 'field' => 'name']);
        }

        $newId = locationCreate($pdo, $level, $parentId, $name, $type);
        locationRespond(201, ['success' => true, 'id' => $newId, 'message' => "{$displayName} added."]);
    }

    // update
    $id = (int) ($input['id'] ?? 0);
    $row = locationFind($pdo, $level, $id);
    if ($row === null) {
        locationRespond(404, ['success' => false, 'message' => "That {$label} no longer exists. Refresh the list."]);
    }
    $parentId = $config['parentColumn'] !== null ? (int) $row[$config['parentColumn']] : null;
    $displayName = $level === 'province' || $level === 'regency' ? locationTitleCase($name) : $name;
    if (locationIsDuplicate($pdo, $level, $parentId, $displayName, $type, $id)) {
        locationRespond(409, ['success' => false, 'message' => "{$displayName} is already in the list.", 'field' => 'name']);
    }

    locationUpdate($pdo, $level, $id, $name, $type);
    locationRespond(200, ['success' => true, 'id' => $id, 'message' => "{$displayName} saved."]);
} catch (PDOException $error) {
    $message = str_contains($error->getMessage(), 'FOREIGN KEY')
        ? "This {$label} is still used elsewhere, so it can't be changed that way."
        : 'Database error: ' . $error->getMessage();
    locationRespond(500, ['success' => false, 'message' => $message]);
}
