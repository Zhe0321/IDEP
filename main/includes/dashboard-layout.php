<?php
declare(strict_types=1);

if (!isset(
    $isAdmin,
    $userInitials,
    $userName,
    $userRole,
    $accessLabel,
    $accessDescription,
    $currentPage
)) {
    http_response_code(500);
    exit('Dashboard role configuration is missing.');
}

require __DIR__ . '/well-data.php';
require __DIR__ . '/operations-data.php';

$rolePrefix = $isAdmin ? 'admin' : 'public';
$pageDetails = [
    'dashboard' => [
        'title' => 'Dashboard',
        'subtitle' => 'IoT groundwater overview for Bali Water Protection monitoring wells',
    ],
    'wells' => [
        'title' => 'Dashboard',
        'subtitle' => 'IoT groundwater overview for Bali Water Protection monitoring wells',
    ],
    'map' => [
        'title' => 'Map View',
        'subtitle' => 'GIS placeholder for groundwater sensor locations across Bali',
    ],
    'historical' => [
        'title' => 'Historical Data',
        'subtitle' => 'Historical measurements, data quality and export controls',
    ],
    'alerts' => [
        'title' => 'Alerts',
        'subtitle' => 'Abnormal readings, battery issues and missing transmissions',
    ],
    'reports' => [
        'title' => 'Reports',
        'subtitle' => 'Generate IDEP groundwater monitoring summaries and exports',
    ],
    'registration' => [
        'title' => 'Hardware',
        'subtitle' => 'Register and manage recharge well sensor hardware',
    ],
    'settings' => [
        'title' => 'Settings',
        'subtitle' => 'Sensor thresholds, users, notification rules and integrations',
    ],
];

if (!array_key_exists($currentPage, $pageDetails)) {
    http_response_code(404);
    exit('Dashboard page not found.');
}

$pageTitle = $pageDetails[$currentPage]['title'];
$pageSubtitle = $pageDetails[$currentPage]['subtitle'];
$pageFile = __DIR__ . '/pages/' . $currentPage . '-page.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Bali Water Protection groundwater monitoring dashboard.">
  <title><?= htmlspecialchars($pageTitle) ?> | Bali Water Protection</title>
  <link rel="icon" href="/images/brand/bwp-mark.png" type="image/png">
  <link rel="stylesheet" href="/main/css/dashboard.css">
  <?php if ($currentPage === 'map'): ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
  <?php endif; ?>
  <script src="/main/js/dashboard.js" defer></script>
  <?php if ($currentPage === 'map'): ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js" defer></script>
    <script src="/main/js/leaflet-map.js" defer></script>
  <?php endif; ?>
</head>
<body data-page="<?= htmlspecialchars($currentPage) ?>">
  <div class="dashboard-shell">
    <aside class="sidebar">
      <a class="sidebar-logo" href="/" aria-label="Bali Water Protection home">
        <img src="/images/brand/bwp-logo-dark.png" alt="Bali Water Protection">
      </a>

      <a class="back-home" href="/index.php#impact">← Home / Impact</a>

      <nav class="sidebar-nav" aria-label="Dashboard navigation">
        <a class="<?= $currentPage === 'dashboard' ? 'active' : '' ?>" href="/main/<?= $rolePrefix ?>-dashboard.php">
          <span class="nav-icon" aria-hidden="true">⌂</span>Dashboard
        </a>
        <a class="<?= $currentPage === 'wells' ? 'active' : '' ?>" href="/main/<?= $rolePrefix ?>-monitoring-wells.php">
          <span class="nav-icon" aria-hidden="true">◉</span>Monitoring Wells
        </a>
        <a class="<?= $currentPage === 'map' ? 'active' : '' ?>" href="/main/<?= $rolePrefix ?>-map-view.php">
          <span class="nav-icon" aria-hidden="true">⌖</span>Map View
        </a>

        <?php if ($isAdmin): ?>
          <a class="<?= $currentPage === 'historical' ? 'active' : '' ?>" href="/main/admin-historical-data.php"><span class="nav-icon" aria-hidden="true">↗</span>Historical Data</a>
          <a class="<?= $currentPage === 'alerts' ? 'active' : '' ?>" href="/main/admin-alerts.php"><span class="nav-icon" aria-hidden="true">♧</span>Alerts</a>
          <a class="<?= $currentPage === 'reports' ? 'active' : '' ?>" href="/main/admin-reports.php"><span class="nav-icon" aria-hidden="true">▤</span>Reports</a>
          <a class="<?= $currentPage === 'registration' ? 'active' : '' ?>" href="/main/admin-site-registration.php"><span class="nav-icon" aria-hidden="true">▣</span>Site Registration</a>
          <a class="<?= $currentPage === 'settings' ? 'active' : '' ?>" href="/main/admin-settings.php"><span class="nav-icon" aria-hidden="true">⚙</span>Settings</a>
        <?php endif; ?>
      </nav>

      <?php if (!$isAdmin): ?>
        <div class="access-card">
          <strong><?= htmlspecialchars($accessLabel) ?></strong>
          <span><?= htmlspecialchars($accessDescription) ?></span>
        </div>
      <?php endif; ?>

      <div class="sidebar-bottom">
        <a class="session-link" href="/main/login.php">
          <span aria-hidden="true">⇥</span><?= $isAdmin ? 'Logout' : 'Login' ?>
        </a>
        <div class="support-card">
          <span class="support-dot" aria-hidden="true">?</span>
          <p><strong>Need Help?</strong><span>Contact IDEP support</span></p>
        </div>
      </div>
    </aside>

    <div class="dashboard-area">
      <header class="dashboard-header">
        <div class="header-copy">
          <h1><?= htmlspecialchars($pageTitle) ?></h1>
          <p><?= htmlspecialchars($pageSubtitle) ?></p>
        </div>

        <label class="dashboard-search" for="well-search">
          <span aria-hidden="true">⌕</span>
          <input id="well-search" type="search" placeholder="Search wells or villages" autocomplete="off">
        </label>

        <div class="user-summary">
          <span class="avatar"><?= htmlspecialchars($userInitials) ?></span>
          <p><strong><?= htmlspecialchars($userName) ?></strong><span><?= htmlspecialchars($userRole) ?></span></p>
        </div>
      </header>

      <main class="dashboard-content dashboard-content--<?= htmlspecialchars($currentPage) ?>">
        <?php require $pageFile; ?>
      </main>
    </div>
  </div>
</body>
</html>
