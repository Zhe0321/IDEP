<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../database/db.php';
$provinces = [];
$registrationDatabaseError = null;
try {
    $pdo = idepDatabase();
    $provinces = $pdo->query("SELECT id, name FROM province WHERE status IS NULL OR status = 1 ORDER BY name ASC")->fetchAll();
} catch (Throwable $error) {
    $registrationDatabaseError = 'Location data is unavailable. Run the database migration and Bali location import.';
    error_log('Registration location query failed: ' . $error->getMessage());
}

$cities = array_values(array_unique(array_column($wells, 'city')));
sort($cities);
?>
<section class="filter-bar filter-bar--registration" aria-label="Hardware filters">
  <label class="filter-field"><span>◫ Location (City)</span><select data-hardware-filter="city"><option value="">All Bali (Aggregate)</option><?php foreach ($cities as $city): ?><option value="<?= htmlspecialchars($city) ?>"><?= htmlspecialchars($city) ?></option><?php endforeach; ?></select></label>
  <label class="filter-field"><span>● Well ID</span><select data-hardware-filter="name"><option value="">All Wells in City</option><?php foreach ($hardwareRecords as $record): ?><option value="<?= htmlspecialchars($record['name']) ?>"><?= htmlspecialchars($record['name']) ?> · <?= htmlspecialchars($record['city']) ?></option><?php endforeach; ?></select></label>
  <label class="filter-field"><span>▣ Start Date</span><input type="date" data-hardware-filter="start-date"></label>
  <label class="filter-field"><span>▦ End Date</span><input type="date" data-hardware-filter="end-date"></label>
</section>

<div class="registration-toolbar">
  <button type="button" data-open-hardware-form>Add new site <strong>＋</strong></button>
  <button type="button" data-close-hardware-form hidden>Close form <strong>×</strong></button>
</div>

<form class="panel hardware-form" data-hardware-form hidden>
  <h2>Add Hardware</h2>
  <?php if ($registrationDatabaseError !== null): ?><p class="form-error"><?= htmlspecialchars($registrationDatabaseError) ?></p><?php endif; ?>
  <input type="hidden" name="well_id" value="">
  <div class="hardware-form-grid">
    <label><span>ID/Name</span><input name="name" required></label>
    <label><span>Sensor Device ID</span><input name="sensor_id" required></label>
    <label><span>Installer / Contractor Name</span><input name="installer" required></label>
    <label><span>Type of well</span><select name="type" required><option value="">Select type</option><option>Type 1</option><option>Type 2</option><option>Type 3</option><option>Type 4</option></select></label>
    <label><span>Installation Date</span><input name="date" type="date" required></label>
    <label><span>Longitude</span><input name="longitude" inputmode="decimal"></label>

    <label><span>Province</span>
      <select name="province_id" id="province_id" required <?= $registrationDatabaseError !== null ? 'disabled' : '' ?>>
        <option value="">Select province</option>
        <?php foreach ($provinces as $p): ?>
          <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <label><span>District / City</span>
      <select name="district_id" id="district_id" required disabled>
        <option value="">Select province first</option>
      </select>
    </label>

    <label><span>Sub-district</span>
      <select name="sub_district_id" id="sub_district_id" required disabled>
        <option value="">Select district first</option>
      </select>
    </label>

    <label><span>Village</span>
      <select name="village_id" id="village_id" required disabled>
        <option value="">Select sub-district first</option>
      </select>
    </label>

    <label><span>Latitude</span><input name="latitude" inputmode="decimal"></label>
  </div>
  <div class="hardware-form-actions"><span data-hardware-message hidden></span><button type="submit" data-hardware-submit>Add device</button></div>
</form>

<section class="registered-sites">
  <h2>Registered Sites</h2>
  <article class="panel hardware-list-panel">
    <h3>Hardware List</h3>
    <div class="table-scroll">
      <table class="operations-table hardware-table">
        <thead><tr><th>ID Device and location</th><th>Hardware tracking</th><th>Date added</th><th>Installation Details</th><th>Edit/Delete</th></tr></thead>
        <tbody data-hardware-table>
          <?php if (empty($hardwareRecords)): ?>
            <tr><td colspan="5">No hardware registered yet.</td></tr>
          <?php else: ?>
            <?php foreach ($hardwareRecords as $record): ?>
              <tr
                data-hardware-row
                data-well-id="<?= (int)($record['wellId'] ?? 0) ?>"
                data-name="<?= htmlspecialchars($record['name'] ?? '') ?>"
                data-city="<?= htmlspecialchars($record['city'] ?? '') ?>"
                data-mac="<?= htmlspecialchars($record['mac'] ?? '') ?>"
                data-sensor-id="<?= htmlspecialchars($record['sensorDeviceId'] ?? '') ?>"
                data-province-id="<?= (int)($record['provinceId'] ?? 0) ?>"
                data-district-id="<?= (int)($record['districtId'] ?? 0) ?>"
                data-sub-district-id="<?= (int)($record['subDistrictId'] ?? 0) ?>"
                data-village-id="<?= (int)($record['villageId'] ?? 0) ?>"
                data-date="<?= htmlspecialchars($record['date'] ?? '') ?>"
                data-installer="<?= htmlspecialchars($record['installer'] ?? '') ?>"
                data-type="<?= htmlspecialchars($record['type'] ?? '') ?>"
                data-longitude="<?= htmlspecialchars((string)($record['longitude'] ?? '')) ?>"
                data-latitude="<?= htmlspecialchars((string)($record['latitude'] ?? '')) ?>"
              >
                <td><?= htmlspecialchars($record['name'] ?? '') ?> - <?= htmlspecialchars($record['city'] ?? '') ?></td>
                <td>Sensor: <?= htmlspecialchars($record['sensorDeviceId'] ?? '—') ?></td>
                <td><?= htmlspecialchars($record['displayDate'] ?? '') ?></td>
                <td><?= htmlspecialchars($record['installer'] ?? '') ?></td>
                <td><button class="row-icon-button" type="button" data-edit-row aria-label="Edit <?= htmlspecialchars($record['name'] ?? '') ?>">✎</button><button class="row-icon-button" type="button" data-remove-row aria-label="Delete <?= htmlspecialchars($record['name'] ?? '') ?>">▣</button></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <footer class="hardware-list-footer"><span>Rows per page <select><option>10</option></select></span><span data-hardware-count>Showing <?= count($hardwareRecords ?? []) ?> entries</span><div><button type="button">←</button><button type="button">→</button></div></footer>
  </article>
</section>
