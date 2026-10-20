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
  <button class="registration-toolbar__secondary" type="button" data-open-location-manager>Add location <strong>＋</strong></button>
  <button type="button" data-open-hardware-form>Add new site <strong>＋</strong></button>
  <button type="button" data-close-hardware-form hidden>Close form <strong>×</strong></button>
</div>

<form class="panel hardware-form" data-hardware-form hidden>
  <h2>Add Hardware</h2>
  <?php if ($registrationDatabaseError !== null): ?><p class="form-error"><?= htmlspecialchars($registrationDatabaseError) ?></p><?php endif; ?>
  <input type="hidden" name="well_id" value="">
  <div class="hardware-form-grid">
    <label><span>ID/Name</span><input name="name" required></label>
    <label><span>Sensor Device ID</span><input name="sensor_id" placeholder="Optional until a sensor is connected"></label>
    <label><span>Installer / Contractor Name</span><input name="installer" required></label>
    <label>
      <span>Type of well</span>
      <select name="type" required>
        <option value="">Select type</option>
        <option>Type 1</option>
        <option>Type 2</option>
        <option>Type 3</option>
        <option>Type 4</option>
        <option>Boring Well</option>
        <option>Monitoring Well</option>
        <option>Manual Well</option>
      </select>
    </label>
    <label><span>Depth (metres)</span><input name="depth" inputmode="decimal"></label>
    <label><span>Absorbance Capacity</span><input name="absorption" placeholder="e.g. 2 m3/hr"></label>
    <label class="hardware-form-grid__wide"><span>Installation Date</span><input name="date" type="date" required></label>

    <div class="hardware-form-grid__coordinates">
      <label><span>Longitude</span><input name="longitude" inputmode="decimal"></label>
      <label><span>Latitude</span><input name="latitude" inputmode="decimal"></label>
    </div>

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

    <label class="hardware-form-grid__wide"><span>Address</span>    <div class="hardware-form-grid__wide hardware-photo-field">
      <label><span>Site photo (JPG, PNG or WebP, max 5 MB)</span>
        <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" data-hardware-photo-input>
      </label>
      <div class="hardware-photo-preview" data-hardware-photo-preview hidden>
        <img alt="Site photo preview" data-hardware-photo-img>
        <label class="hardware-photo-remove"><input type="checkbox" name="remove_photo" value="1" data-hardware-photo-remove> Remove photo</label>
      </div>
    </div><input name="address"></label>
  </div>
  <div class="hardware-form-actions"><span data-hardware-message hidden></span><button type="submit" data-hardware-submit>Add device</button></div>
</form>

<section class="registered-sites">
  <h2>Registered Sites</h2>
  <article class="panel hardware-list-panel">
    <h3>Hardware List</h3>
    <div class="table-scroll">
      <table class="operations-table hardware-table">
        <thead><tr><th>Well ID / Site</th><th>Type / Depth</th><th>District</th><th>Absorbance</th><th>Sensor</th><th>Installed</th><th>Edit/Delete</th></tr></thead>
        <tbody data-hardware-table>
          <?php if (empty($hardwareRecords)): ?>
            <tr><td colspan="7">No sites registered yet.</td></tr>
          <?php else: ?>
            <?php foreach ($hardwareRecords as $record): ?>
              <tr
                data-hardware-row
                data-well-id="<?= (int)($record['wellId'] ?? 0) ?>"
                data-well-code="<?= htmlspecialchars($record['wellCode'] ?? '') ?>"
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
                data-depth="<?= htmlspecialchars((string)($record['depth'] ?? '')) ?>"
                data-absorption="<?= htmlspecialchars($record['absorption'] ?? '') ?>"
                data-address="<?= htmlspecialchars($record['address'] ?? '') ?>"
                data-longitude="<?= htmlspecialchars((string)($record['longitude'] ?? '')) ?>"
                data-latitude="<?= htmlspecialchars((string)($record['latitude'] ?? '')) ?>"
                data-photo="<?= htmlspecialchars((string)($record['photo'] ?? '')) ?>"
              >
                <td><div class="hardware-site"><?php if (!empty($record['photo'])): ?><img class="hardware-thumb" src="<?= htmlspecialchars($record['photo']) ?>" alt="" loading="lazy"><?php endif; ?><span><strong><?= htmlspecialchars($record['wellCode'] ?? '') ?></strong><br><?= htmlspecialchars($record['name'] ?? '') ?></span></div></td>                <td><?= htmlspecialchars($record['type'] ?? '—') ?><br><?= $record['depth'] !== null ? htmlspecialchars((string)$record['depth']) . ' m' : '—' ?></td>
                <td><?= htmlspecialchars($record['city'] ?? '') ?><br><small><?= htmlspecialchars($record['address'] ?? '—') ?></small></td>
                <td><?= htmlspecialchars($record['absorption'] ?? '—') ?></td>
                <td><?= htmlspecialchars($record['sensorDeviceId'] ?? 'No Signal') ?></td>
                <td><?= htmlspecialchars($record['displayDate'] ?? '') ?></td>
                <td><button class="row-icon-button" type="button" data-edit-row aria-label="Edit <?= htmlspecialchars($record['name'] ?? '') ?>">✎</button><button class="row-icon-button" type="button" data-remove-row aria-label="Delete <?= htmlspecialchars($record['name'] ?? '') ?>">▣</button></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <footer class="hardware-list-footer"><span>Rows per page <select data-hardware-page-size><option>10</option><option>25</option><option>50</option><option>100</option></select></span><span data-hardware-count>Showing <?= count($hardwareRecords ?? []) ?> entries</span><div><button type="button" data-hardware-prev>←</button><button type="button" data-hardware-next>→</button></div></footer>
  </article>
</section>

<?php require __DIR__ . '/../location-manager.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const provinceSelect    = document.getElementById('province_id');
  const districtSelect    = document.getElementById('district_id');
  const subDistrictSelect = document.getElementById('sub_district_id');
  const villageSelect     = document.getElementById('village_id');

  function resetSelect(select, placeholder) {
    select.innerHTML = `<option value="">${placeholder}</option>`;
    select.disabled = true;
  }

  function populateSelect(select, items, placeholder) {
    select.innerHTML = `<option value="">${placeholder}</option>`;
    items.forEach(item => {
      const opt = document.createElement('option');
      opt.value = item.id;
      opt.textContent = item.name;
      select.appendChild(opt);
    });
    select.disabled = false;
  }

  provinceSelect.addEventListener('change', function () {
    resetSelect(districtSelect, 'Select district first');
    resetSelect(subDistrictSelect, 'Select district first');
    resetSelect(villageSelect, 'Select sub-district first');
    if (!this.value) return;

    fetch(`/main/ajax-get-districts.php?province_id=${this.value}`)
      .then(res => res.json())
      .then(data => populateSelect(districtSelect, data, 'Select district/city'));
  });

  districtSelect.addEventListener('change', function () {
    resetSelect(subDistrictSelect, 'Select sub-district first');
    resetSelect(villageSelect, 'Select sub-district first');
    if (!this.value) return;

    fetch(`/main/ajax-get-subdistricts.php?district_id=${this.value}`)
      .then(res => res.json())
      .then(data => populateSelect(subDistrictSelect, data, 'Select sub-district'));
  });

  // Reload the province list after locations are added/edited in the Add Location dialog.
  document.addEventListener('idep:locations-changed', function () {
    const selectedProvince = provinceSelect.value;
    fetch('/main/api/locations.php?level=province', { headers: { 'X-Requested-With': 'fetch' } })
      .then(res => res.json())
      .then(data => {
        if (!data.success) return;
        populateSelect(provinceSelect, data.items, 'Select province');
        resetSelect(districtSelect, 'Select province first');
        resetSelect(subDistrictSelect, 'Select district first');
        resetSelect(villageSelect, 'Select sub-district first');
        if (data.items.some(item => String(item.id) === selectedProvince)) {
          provinceSelect.value = selectedProvince;
          provinceSelect.dispatchEvent(new Event('change'));
        }
      });
  });

  subDistrictSelect.addEventListener('change', function () {
    resetSelect(villageSelect, 'Select village');
    if (!this.value) return;

    fetch(`/main/ajax-get-villages.php?sub_district_id=${this.value}`)
      .then(res => res.json())
      .then(data => populateSelect(villageSelect, data, 'Select village'));
  });
});
</script>