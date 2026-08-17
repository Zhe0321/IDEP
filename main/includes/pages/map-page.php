<?php
declare(strict_types=1);

$cities = array_values(array_unique(array_column($wells, 'city')));
sort($cities);
$mapMode = 'large';
?>
<section class="filter-bar filter-bar--map" aria-label="Map filters">
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
        <option value="<?= strtolower(htmlspecialchars($well['id'])) ?>"><?= htmlspecialchars($well['id']) ?> · <?= htmlspecialchars($well['city']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>

  <label class="filter-field">
    <span>▣ Start Date</span>
    <input type="date" value="2026-08-01" data-filter="start-date">
  </label>

  <label class="filter-field">
    <span>▦ End Date</span>
    <input type="date" value="2026-08-20" data-filter="end-date">
  </label>
</section>

<section class="panel distribution-panel">
  <div>
    <h2>🗺 Geographic Distribution</h2>
    <p>Visual overview of 76 recharge wells</p>
  </div>
  <ul class="status-legend" aria-label="Map status colours">
    <li><i class="status-dot status-dot--online"></i><strong>Green:</strong> Online</li>
    <li><i class="status-dot status-dot--offline"></i><strong>Red:</strong> Offline</li>
    <li><i class="status-dot status-dot--no-signal"></i><strong>Grey:</strong> No Signal</li>
  </ul>
</section>

<section class="panel full-map-panel">
  <div class="section-heading">
    <div>
      <h2>Map View · Monitoring and Recharge Wells</h2>
      <p>Click a coloured marker to view the well photo and information.</p>
    </div>
    <a class="small-button" href="https://www.google.com/maps/search/?api=1&query=Bali%2C%20Indonesia" target="_blank" rel="noreferrer">Open Google Maps ↗</a>
  </div>
  <?php require __DIR__ . '/../map-canvas.php'; ?>
  <p class="prototype-note">Prototype marker positions only. Real GIS coordinates and sensor records will be connected after the database setup is ready.</p>
</section>
