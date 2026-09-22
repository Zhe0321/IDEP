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
    'impacts' => [
        'title' => 'Impacts',
        'subtitle' => 'IoT groundwater overview for Bali Water Protection monitoring wells',
    ],
    'dashboard' => [
        'title' => 'Dashboard',
        'subtitle' => 'IoT groundwater overview for Bali Water Protection monitoring wells',
    ],
    'wells' => [
        'title' => 'Monitoring Wells',
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
$dashboardCssVersion = (string) filemtime(__DIR__ . '/../css/dashboard.css');
$dashboardJsVersion = (string) filemtime(__DIR__ . '/../js/dashboard.js');
$leafletMapJsVersion = (string) filemtime(__DIR__ . '/../js/leaflet-map.js');

$navIcons = [
    'home' => '<svg viewBox="0 0 24 24"><path d="M3 11.5 12 4l9 7.5"/><path d="M5.5 10.5V21h13V10.5M9.5 21v-6h5v6"/></svg>',
    'impacts' => '<svg viewBox="0 0 24 24"><path d="M5 20V11M12 20V4M19 20v-13"/></svg>',
    'dashboard' => '<svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="1"/><path d="M3 9h18M9 9v11"/></svg>',
    'wells' => '<svg viewBox="0 0 24 24"><path d="M12 3s-6 7-6 12a6 6 0 0 0 12 0c0-5-6-12-6-12Z"/><path d="M9.5 15.5a2.7 2.7 0 0 0 2.7 2.2"/></svg>',
    'map' => '<svg viewBox="0 0 24 24"><path d="M20 10c0 5.5-8 11-8 11S4 15.5 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>',
    'historical' => '<svg viewBox="0 0 24 24"><path d="M3 20h18M5 17l4-5 4 3 6-8"/><path d="M15 7h4v4"/></svg>',
    'alerts' => '<svg viewBox="0 0 24 24"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 7h18s-3 0-3-7ZM10 20h4"/></svg>',
    'reports' => '<svg viewBox="0 0 24 24"><path d="M6 3h9l4 4v14H6z"/><path d="M15 3v5h4M9 12h6M9 16h6"/></svg>',
    'registration' => '<svg viewBox="0 0 24 24"><rect x="4" y="5" width="16" height="16" rx="2"/><path d="M9 5V3h6v2M8 10h8"/></svg>',
    'settings' => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2.8 2.8-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6v.2h-4V21a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1L4.2 17l.1-.1a1.7 1.7 0 0 0 .3-1.9A1.7 1.7 0 0 0 3 14H2.8v-4H3a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9L4.2 7 7 4.2l.1.1A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-1.6v-.2h4V3a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1L19.8 7l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.6 1h.2v4H21a1.7 1.7 0 0 0-1.6 1Z"/></svg>',
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Bali Water Protection groundwater monitoring dashboard.">
  <title><?= htmlspecialchars($pageTitle) ?> | Bali Water Protection</title>
  <link rel="icon" href="/images/brand/bwp-mark.png" type="image/png">
  <link rel="stylesheet" href="/main/css/dashboard.css?v=<?= $dashboardCssVersion ?>">
  <?php if (in_array($currentPage, ['map', 'dashboard'], true)): ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
  <?php endif; ?>
  <script src="/main/js/dashboard.js?v=<?= $dashboardJsVersion ?>" defer></script>
  <?php if (in_array($currentPage, ['map', 'dashboard'], true)): ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js" defer></script>
    <script src="/main/js/leaflet-map.js?v=<?= $leafletMapJsVersion ?>" defer></script>
  <?php endif; ?>
</head>
<body data-page="<?= htmlspecialchars($currentPage) ?>">
  <div class="dashboard-shell">
    <aside class="sidebar">
      <a class="sidebar-logo" href="/" aria-label="Bali Water Protection home">
        <img src="/images/brand/idep-bwp-logo-white.png" alt="IDEP Foundation and Bali Water Protection">
      </a>

      <nav class="sidebar-nav" aria-label="Dashboard navigation">
        <a href="/index.php">
          <span class="nav-icon" aria-hidden="true"><?= $navIcons['home'] ?></span>Home
        </a>
        <a class="<?= $currentPage === 'impacts' ? 'active' : '' ?>" href="/main/<?= $rolePrefix ?>-impacts.php">
          <span class="nav-icon" aria-hidden="true"><?= $navIcons['impacts'] ?></span>Impacts
        </a>
        <a class="<?= $currentPage === 'dashboard' ? 'active' : '' ?>" href="/main/<?= $rolePrefix ?>-dashboard.php">
          <span class="nav-icon" aria-hidden="true"><?= $navIcons['dashboard'] ?></span>Project Overview
        </a>
        <a class="<?= $currentPage === 'wells' ? 'active' : '' ?>" href="/main/<?= $rolePrefix ?>-monitoring-wells.php">
          <span class="nav-icon" aria-hidden="true"><?= $navIcons['wells'] ?></span>Monitoring Wells
        </a>
        <a class="<?= $currentPage === 'map' ? 'active' : '' ?>" href="/main/<?= $rolePrefix ?>-map-view.php">
          <span class="nav-icon" aria-hidden="true"><?= $navIcons['map'] ?></span>Map View
        </a>

        <?php if ($isAdmin): ?>
          <a class="<?= $currentPage === 'historical' ? 'active' : '' ?>" href="/main/admin-historical-data.php"><span class="nav-icon" aria-hidden="true"><?= $navIcons['historical'] ?></span>Historical Data</a>
          <a class="<?= $currentPage === 'alerts' ? 'active' : '' ?>" href="/main/admin-alerts.php"><span class="nav-icon" aria-hidden="true"><?= $navIcons['alerts'] ?></span>Alerts</a>
          <a class="<?= $currentPage === 'reports' ? 'active' : '' ?>" href="/main/admin-reports.php"><span class="nav-icon" aria-hidden="true"><?= $navIcons['reports'] ?></span>Reports</a>
          <a class="<?= $currentPage === 'registration' ? 'active' : '' ?>" href="/main/admin-site-registration.php"><span class="nav-icon" aria-hidden="true"><?= $navIcons['registration'] ?></span>Site Registration</a>
          <a class="<?= $currentPage === 'settings' ? 'active' : '' ?>" href="/main/admin-settings.php"><span class="nav-icon" aria-hidden="true"><?= $navIcons['settings'] ?></span>Settings</a>
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
