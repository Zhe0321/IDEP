<?php
declare(strict_types=1);

$mapMode = 'compact';
?>
<section class="panel dashboard-map-panel">
  <div class="section-heading">
    <div>
      <h2>Groundwater Monitoring Wells Map</h2>
      <p>Live well locations for Bali Water Protection recharge wells.</p>
    </div>
    <a class="small-button" href="/main/<?= $rolePrefix ?>-map-view.php">Go to Map →</a>
  </div>
  <?php require __DIR__ . '/../leaflet-map.php'; ?>
</section>

<section class="stats-grid" aria-label="Programme statistics">
  <article class="stat-card">
    <span class="stat-icon" aria-hidden="true">◊</span>
    <div><span>Recharge Wells</span><strong data-programme-metric="well-count"><?= (int)$programmeMetrics['wellCount'] ?></strong><small>Active database records</small></div>
  </article>
  <article class="stat-card">
    <span class="stat-icon" aria-hidden="true">≈</span>
    <div><span>Online Sensors</span><strong data-live-online-sensors>— / 1</strong><small data-live-sensor-status>Checking device_1…</small></div>
  </article>
  <article class="stat-card" id="alerts">
    <span class="stat-icon" aria-hidden="true">!</span>
    <div><span>Active Alerts</span><strong><?= count($alerts) ?></strong><small><?= count($alerts) === 0 ? 'No field review required' : 'Requires field review' ?></small></div>
  </article>
  <article class="stat-card">
    <span class="stat-icon" aria-hidden="true">≈</span>
    <div><span>Avg. Water Absorb</span><strong data-programme-metric="daily-average-absorb"><?= htmlspecialchars((string)$programmeMetrics['dailyAverageAbsorbLabel']) ?></strong><small>Average of today's readings · WITA</small></div>
  </article>
</section>

<section class="dashboard-overview-grid">
  <article class="panel measurements-panel">
    <h2>Recent Sensor Measurements</h2>
    <div class="measurement-list live-sensor" data-live-sensor aria-live="polite">
      <div class="measurement-row live-sensor__heading">
        <span><i class="status-dot status-dot--no-signal" data-live-status-dot></i><strong data-live-device>device_1</strong></span>
        <strong data-live-connection>Loading…</strong>
        <time data-live-received>—</time>
      </div>
      <dl class="live-sensor__values">
        <div><dt>H1</dt><dd data-live-h1>—</dd></div>
        <div><dt>H2</dt><dd data-live-h2>—</dd></div>
        <div><dt>Hasil</dt><dd data-live-hasil>—</dd></div>
      </dl>
    </div>
    <small class="live-sensor__note">Automatically refreshes every 60 seconds · Bali time (WITA)</small>
  </article>

  <a class="dashboard-photo" href="/main/<?= $rolePrefix ?>-monitoring-wells.php" aria-label="View monitoring well inventory">
    <img src="/images/homepageIMG/recharge-well-introduction.jpg" alt="Community members visiting a Bali recharge well">
    <span>Explore monitoring wells →</span>
  </a>
</section>
