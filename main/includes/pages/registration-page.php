<?php
declare(strict_types=1);

$cities = array_values(array_unique(array_column($wells, 'city')));
sort($cities);
?>
<section class="filter-bar filter-bar--registration" aria-label="Hardware filters">
  <label class="filter-field"><span>◫ Location (City)</span><select><option>All Bali (Aggregate)</option><?php foreach ($cities as $city): ?><option><?= htmlspecialchars($city) ?></option><?php endforeach; ?></select></label>
  <label class="filter-field"><span>● Well ID</span><select><option>All Wells in City</option><?php foreach ($wells as $well): ?><option><?= htmlspecialchars($well['id']) ?> · <?= htmlspecialchars($well['city']) ?></option><?php endforeach; ?></select></label>
  <label class="filter-field"><span>▣ Start Date</span><input type="date"></label>
  <label class="filter-field"><span>▦ End Date</span><input type="date"></label>
</section>

<div class="registration-toolbar">
  <button type="button" data-open-hardware-form>Add new site <strong>＋</strong></button>
  <button type="button" data-close-hardware-form hidden>Close form <strong>×</strong></button>
</div>

<form class="panel hardware-form" data-hardware-form hidden>
  <h2>Add Hardware</h2>
  <div class="hardware-form-grid">
    <label><span>ID/Name</span><input name="name" required></label>
    <label><span>Installer / Contractor Name</span><input name="installer" required></label>
    <label><span>Type of well</span><select name="type" required><option value="">Select type</option><option>Type 1</option><option>Type 2</option><option>Type 3</option><option>Type 4</option></select></label>
    <label><span>Installation Date</span><input name="date" type="date" required></label>
    <label><span>Longitude</span><input name="longitude" inputmode="decimal"></label>
    <label><span>Location (City)</span><select name="city" required><option value="">Select city</option><?php foreach ($cities as $city): ?><option><?= htmlspecialchars($city) ?></option><?php endforeach; ?></select></label>
    <label><span>Latitude</span><input name="latitude" inputmode="decimal"></label>
    <label><span>Sensor Hardware ID (MAC)</span><input name="mac" required></label>
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
          <?php foreach ($hardwareRecords as $record): ?>
            <tr
              data-hardware-row
              data-name="<?= htmlspecialchars($record['name']) ?>"
              data-city="<?= htmlspecialchars($record['city']) ?>"
              data-mac="<?= htmlspecialchars($record['mac']) ?>"
              data-date="<?= htmlspecialchars($record['date']) ?>"
              data-installer="<?= htmlspecialchars($record['installer']) ?>"
              data-type="<?= htmlspecialchars($record['type']) ?>"
              data-longitude="<?= htmlspecialchars($record['longitude']) ?>"
              data-latitude="<?= htmlspecialchars($record['latitude']) ?>"
            >
              <td><?= htmlspecialchars($record['name']) ?> - <?= htmlspecialchars($record['city']) ?></td>
              <td>MAC: <?= htmlspecialchars($record['mac']) ?></td>
              <td><?= htmlspecialchars($record['displayDate']) ?></td>
              <td><?= htmlspecialchars($record['installer']) ?></td>
              <td><button class="row-icon-button" type="button" data-edit-row aria-label="Edit <?= htmlspecialchars($record['name']) ?>">✎</button><button class="row-icon-button" type="button" data-remove-row aria-label="Delete <?= htmlspecialchars($record['name']) ?>">▣</button></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <footer class="hardware-list-footer"><span>Rows per page <select><option>10</option></select></span><span data-hardware-count>Showing 1 to 2 of 2 entries</span><div><button type="button">←</button><button type="button">→</button></div></footer>
  </article>
</section>
