<?php
declare(strict_types=1);

$selectedWell = $wells[0];
$cities = array_values(array_unique(array_column($wells, 'city')));
sort($cities);

$totalWells = count($wells);
$onlineWells = count(array_filter($wells, static fn (array $well): bool => $well['status'] === 'online'));
$offlineWells = count(array_filter($wells, static fn (array $well): bool => $well['status'] === 'offline'));
$noSignalWells = count(array_filter($wells, static fn (array $well): bool => $well['status'] === 'no-signal'));
?>
<section class="filter-bar" aria-label="Well filters">
  <label class="filter-field">
    <span>◫ Location (City)</span>
    <select data-filter="city">
      <option value="">All Bali (Aggregate)</option>
      <?php foreach ($cities as $city): ?>
        <option value="<?= strtolower(htmlspecialchars($city)) ?>"><?= htmlspecialchars($city) ?></option>
      <?php endforeach; ?>
    </select>
  </label>

  <label class="filter-field">
    <span>● Well ID</span>
    <select data-filter="id">
      <option value="">All Wells in City</option>
      <?php foreach ($wells as $well): ?>
        <option value="<?= strtolower(htmlspecialchars($well['id'])) ?>" data-city="<?= strtolower(htmlspecialchars($well['city'])) ?>"><?= htmlspecialchars($well['id']) ?> · <?= htmlspecialchars($well['city']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>

  <?php if ($isAdmin): ?>
    <label class="filter-field">
      <span>● Sensor Status</span>
      <select data-filter="status">
        <option value="">All Status</option>
        <option value="online">Online</option>
        <option value="offline">Offline</option>
        <option value="no-signal">No Signal</option>
      </select>
    </label>
  <?php endif; ?>

  <label class="filter-field">
    <span>▣ Start Date</span>
    <input type="date" data-filter="start-date">
  </label>

  <label class="filter-field">
    <span>▦ End Date</span>
    <input type="date" data-filter="end-date">
  </label>
</section>

<section class="stats-grid" aria-label="Monitoring wells summary" data-wells-stats>
  <article class="stat-card">
    <span class="stat-icon" aria-hidden="true">◊</span>
    <div><span>Wells Shown</span><strong data-stat="total"><?= $totalWells ?></strong><small>Matches current filters</small></div>
  </article>
  <article class="stat-card">
    <span class="stat-icon" aria-hidden="true">●</span>
    <div><span>Online</span><strong data-stat="online"><?= $onlineWells ?></strong><small>Transmitting normally</small></div>
  </article>
  <article class="stat-card">
    <span class="stat-icon" aria-hidden="true">●</span>
    <div><span>Offline</span><strong data-stat="offline"><?= $offlineWells ?></strong><small>Needs field review</small></div>
  </article>
  <article class="stat-card">
    <span class="stat-icon" aria-hidden="true">●</span>
    <div><span>No Signal</span><strong data-stat="no-signal"><?= $noSignalWells ?></strong><small>No sensor connected</small></div>
  </article>
</section>

<section class="panel inventory-panel">
  <div class="section-heading">
    <div><h2>Monitoring Wells Inventory</h2></div>
    <button class="small-button" type="button" data-export-wells>Export CSV ↓</button>
  </div>
  <div class="table-scroll inventory-scroll">
    <table class="inventory-table">
      <thead>
        <tr>
          <th>Well ID</th>
          <th>City</th>
          <th>Start Date</th>
          <th>End Date</th>
          <th>Duration</th>
          <th>Total Water Absorption</th>
          <th>Transmission</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($wells as $well): ?>
          <tr
            tabindex="0"
            data-well-row
            data-search="<?= strtolower(htmlspecialchars($well['id'] . ' ' . $well['city'] . ' ' . $well['statusLabel'])) ?>"
            data-city="<?= strtolower(htmlspecialchars($well['city'])) ?>"
            data-id="<?= strtolower(htmlspecialchars($well['id'])) ?>"
            data-status="<?= htmlspecialchars($well['status']) ?>"
            data-well='<?= htmlspecialchars(json_encode($well, JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?>'
          >
            <td><?= htmlspecialchars($well['id']) ?></td>
            <td><?= htmlspecialchars($well['city']) ?></td>
            <td><?= htmlspecialchars($well['startDate']) ?></td>
            <td><?= htmlspecialchars($well['endDate']) ?></td>
            <td><?= htmlspecialchars($well['duration']) ?></td>
            <td><?= htmlspecialchars($well['absorption']) ?></td>
            <td>
              <span class="signal signal--<?= htmlspecialchars($well['status']) ?>" aria-label="<?= htmlspecialchars($well['transmission']) ?> transmission">
                <i></i><i></i><i></i><i></i>
              </span>
            </td>
            <td><span class="status-pill status-pill--<?= htmlspecialchars($well['status']) ?>"><?= htmlspecialchars($well['statusLabel']) ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="no-results" data-no-results hidden>No wells match the selected filters.</p>
</section>

<section class="panel selected-well" data-selected-well>
  <div class="selected-well__content">
    <h2>Selected Well Overview</h2>
    <div class="selected-well__body">
      <div class="well-metrics">
        <article><span>Well Type</span><strong data-detail="wellType"><?= htmlspecialchars($selectedWell['wellType']) ?></strong></article>
        <article><span>Litres Absorbed every Minute</span><strong data-detail="litresMinute"><?= htmlspecialchars($selectedWell['litresMinute']) ?></strong></article>
        <article><span>Litres per Hour Estimation</span><strong data-detail="litresHour"><?= htmlspecialchars($selectedWell['litresHour']) ?></strong></article>
      </div>
      <dl class="well-details">
        <div><dt>Well ID</dt><dd data-detail="id"><?= htmlspecialchars($selectedWell['id']) ?></dd></div>
        <div><dt>City</dt><dd data-detail="city"><?= htmlspecialchars($selectedWell['city']) ?></dd></div>
        <div><dt>Sensor Status</dt><dd data-detail="statusLabel"><?= htmlspecialchars($selectedWell['statusLabel']) ?></dd></div>
        <div><dt>Last Transmission</dt><dd data-detail="lastTransmission"><?= htmlspecialchars($selectedWell['lastTransmission']) ?></dd></div>
      </dl>
    </div>
  </div>
  <figure class="selected-well__photo">
    <img data-detail-photo src="<?= htmlspecialchars($selectedWell['photo']) ?>" alt="Recharge well placeholder for selected well">
    <figcaption>Temporary well image · Replace when site photos are available</figcaption>
  </figure>
</section>
