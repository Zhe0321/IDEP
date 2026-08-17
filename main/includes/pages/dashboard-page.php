<?php
declare(strict_types=1);

$mapMode = 'compact';
?>
<section class="panel dashboard-map-panel">
  <div class="section-heading">
    <div>
      <h2>Groundwater Monitoring Wells Map</h2>
      <p>Prototype locations for Bali Water Protection recharge wells.</p>
    </div>
    <a class="small-button" href="/main/<?= $rolePrefix ?>-map-view.php">Go to Map →</a>
  </div>
  <?php require __DIR__ . '/../map-canvas.php'; ?>
</section>

<section class="stats-grid" aria-label="Programme statistics">
  <article class="stat-card">
    <span class="stat-icon" aria-hidden="true">◊</span>
    <div><span>Recharge Wells</span><strong>76</strong><small>IDEP project reference</small></div>
  </article>
  <article class="stat-card">
    <span class="stat-icon" aria-hidden="true">≈</span>
    <div><span>Online Sensors</span><strong>22 / 24</strong><small>Sensor status normal</small></div>
  </article>
  <article class="stat-card" id="alerts">
    <span class="stat-icon" aria-hidden="true">!</span>
    <div><span>Active Alerts</span><strong>5</strong><small>Requires field review</small></div>
  </article>
  <article class="stat-card">
    <span class="stat-icon" aria-hidden="true">≈</span>
    <div><span>Avg. Water Level</span><strong>2.45 m</strong><small>Below ground level</small></div>
  </article>
</section>

<section class="dashboard-overview-grid">
  <article class="panel measurements-panel">
    <h2>Recent Sensor Measurements</h2>
    <div class="measurement-list">
      <?php foreach (array_slice($wells, 0, 5) as $well): ?>
        <button
          class="measurement-row"
          type="button"
          data-search="<?= strtolower(htmlspecialchars($well['id'] . ' ' . $well['city'] . ' ' . $well['statusLabel'])) ?>"
          data-well='<?= htmlspecialchars(json_encode($well, JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?>'
        >
          <span><i class="status-dot status-dot--<?= htmlspecialchars($well['status']) ?>"></i><?= htmlspecialchars($well['id']) ?> · <?= htmlspecialchars($well['city']) ?></span>
          <strong><?= htmlspecialchars($well['litresMinute']) ?></strong>
          <time><?= htmlspecialchars($well['lastTransmission']) ?></time>
        </button>
      <?php endforeach; ?>
    </div>
    <a class="text-link" href="/main/<?= $rolePrefix ?>-monitoring-wells.php">View all measurements →</a>
  </article>

  <a class="dashboard-photo" href="/main/<?= $rolePrefix ?>-monitoring-wells.php" aria-label="View monitoring well inventory">
    <img src="/images/homepageIMG/recharge-well-introduction.jpg" alt="Community members visiting a Bali recharge well">
    <span>Explore monitoring wells →</span>
  </a>
</section>
