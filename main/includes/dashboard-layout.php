<?php
declare(strict_types=1);

if (!isset($isAdmin, $userInitials, $userName, $userRole, $accessLabel, $accessDescription)) {
    http_response_code(500);
    exit('Dashboard role configuration is missing.');
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta
    name="description"
    content="Bali Water Protection groundwater monitoring dashboard."
  >
  <title><?= $isAdmin ? 'Admin Dashboard' : 'Public Dashboard' ?> | Bali Water Protection</title>
  <link rel="icon" href="/images/brand/bwp-mark.png" type="image/png">
  <link rel="stylesheet" href="/main/css/dashboard.css">
  <script src="/main/js/dashboard.js" defer></script>
</head>
<body>
  <div class="dashboard-shell">
    <aside class="sidebar">
      <a class="sidebar-logo" href="/" aria-label="Bali Water Protection home">
        <img src="/images/brand/bwp-logo-dark.png" alt="Bali Water Protection">
      </a>

      <a class="back-home" href="/index.php#impact">← Home / Impact</a>

      <nav class="sidebar-nav" aria-label="Dashboard navigation">
        <a class="active" href="#dashboard"><span class="nav-icon" aria-hidden="true">⌂</span>Dashboard</a>
        <a href="#wells"><span class="nav-icon" aria-hidden="true">◉</span>Monitoring Wells</a>
        <a href="#map"><span class="nav-icon" aria-hidden="true">⌖</span>Map View</a>

        <?php if ($isAdmin): ?>
          <a href="#measurements"><span class="nav-icon" aria-hidden="true">↗</span>Historical Data</a>
          <a href="#alerts"><span class="nav-icon" aria-hidden="true">!</span>Alerts</a>
          <a href="#reports"><span class="nav-icon" aria-hidden="true">▤</span>Reports</a>
          <a href="#settings"><span class="nav-icon" aria-hidden="true">⚙</span>Settings</a>
        <?php endif; ?>
      </nav>

      <div class="access-card">
        <strong><?= htmlspecialchars($accessLabel) ?></strong>
        <span><?= htmlspecialchars($accessDescription) ?></span>
      </div>

      <div class="sidebar-bottom">
        <a class="session-link" href="/main/login.php">
          <span aria-hidden="true">⇥</span><?= $isAdmin ? 'Logout' : 'Login' ?>
        </a>
        <div class="support-card">
          <span class="support-dot" aria-hidden="true"></span>
          <p><strong>Need Help?</strong><span>Contact IDEP support</span></p>
        </div>
      </div>
    </aside>

    <div class="dashboard-area" id="dashboard">
      <header class="dashboard-header">
        <div>
          <h1>Dashboard</h1>
          <p>IoT groundwater overview for Bali Water Protection monitoring wells</p>
        </div>

        <label class="dashboard-search" for="well-search">
          <span aria-hidden="true">⌕</span>
          <input id="well-search" type="search" placeholder="Search wells or villages">
        </label>

        <div class="user-summary">
          <span class="avatar"><?= htmlspecialchars($userInitials) ?></span>
          <p><strong><?= htmlspecialchars($userName) ?></strong><span><?= htmlspecialchars($userRole) ?></span></p>
        </div>
      </header>

      <main class="dashboard-content">
        <section class="panel map-panel" id="map">
          <div class="panel-heading map-heading">
            <div>
              <h2>Groundwater Monitoring Wells Map</h2>
              <p>Interactive Google map of Indonesia. Well status markers will be connected to live project data.</p>
            </div>
            <a
              class="map-link"
              href="https://www.google.com/maps/search/?api=1&query=Indonesia"
              target="_blank"
              rel="noreferrer"
            >Open in Google Maps <span aria-hidden="true">↗</span></a>
          </div>

          <div class="map-frame">
            <iframe
              class="google-map"
              src="https://www.google.com/maps?q=Indonesia&z=5&output=embed"
              title="Interactive Google map of Indonesia"
              loading="lazy"
              allowfullscreen
              referrerpolicy="no-referrer-when-downgrade"
            ></iframe>
          </div>
        </section>

        <section class="stats-grid" aria-label="Programme statistics">
          <article class="stat-card" id="wells">
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

        <section class="dashboard-grid" id="measurements">
          <article class="panel measurements-panel">
            <h2>Recent Sensor Measurements</h2>
            <div class="measurement-list">
              <div class="measurement-row" data-search="well 01 ubud"><span><i class="status-dot normal"></i>Well 01 · Ubud</span><strong>2.31 m</strong><time>10 min ago</time></div>
              <div class="measurement-row" data-search="well 07 tabanan"><span><i class="status-dot normal"></i>Well 07 · Tabanan</span><strong>1.82 m</strong><time>20 min ago</time></div>
              <div class="measurement-row" data-search="well 12 denpasar"><span><i class="status-dot warning"></i>Well 12 · Denpasar</span><strong>3.05 m</strong><time>35 min ago</time></div>
              <div class="measurement-row" data-search="well 18 gianyar"><span><i class="status-dot normal"></i>Well 18 · Gianyar</span><strong>2.10 m</strong><time>1 hour ago</time></div>
              <div class="measurement-row" data-search="well 23 badung"><span><i class="status-dot normal"></i>Well 23 · Badung</span><strong>1.67 m</strong><time>2 hours ago</time></div>
            </div>
            <a class="text-link" href="#well-status">View all measurements →</a>
          </article>

          <article class="panel trend-panel">
            <div class="panel-heading">
              <h2>Water Level Trend (Last 7 Days)</h2>
              <span class="filter-label">All Wells</span>
            </div>
            <div class="chart-wrap">
              <canvas id="water-level-chart" aria-label="Seven day water level trend chart"></canvas>
            </div>
          </article>
        </section>

        <section class="panel table-panel" id="well-status">
          <div class="panel-heading">
            <h2>Well Status Table</h2>
            <a class="text-link" href="#well-status">View all wells →</a>
          </div>

          <div class="table-scroll">
            <table>
              <thead>
                <tr>
                  <th>Well ID</th>
                  <th>City</th>
                  <th>Water Level</th>
                  <th>Device Status</th>
                  <th>Signal</th>
                  <th>Last Transmission</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <tr data-search="well 01 ubud online normal">
                  <td>Well 01</td><td>Ubud</td><td>2.31 m</td><td>Online</td><td><span class="signal">▂▄▆█</span></td><td>10 min ago</td><td><span class="status-pill normal">Normal</span></td>
                </tr>
                <tr data-search="well 02 tabanan online normal">
                  <td>Well 02</td><td>Tabanan</td><td>1.98 m</td><td>Online</td><td><span class="signal">▂▄▆█</span></td><td>15 min ago</td><td><span class="status-pill normal">Normal</span></td>
                </tr>
                <tr data-search="well 07 denpasar attention warning">
                  <td>Well 07</td><td>Denpasar</td><td>3.05 m</td><td>Online</td><td><span class="signal warning">▂▄▆</span></td><td>35 min ago</td><td><span class="status-pill warning">Review</span></td>
                </tr>
                <tr data-search="well 18 gianyar online normal">
                  <td>Well 18</td><td>Gianyar</td><td>2.10 m</td><td>Online</td><td><span class="signal">▂▄▆█</span></td><td>1 hour ago</td><td><span class="status-pill normal">Normal</span></td>
                </tr>
              </tbody>
            </table>
          </div>
          <p class="no-results" id="no-results" hidden>No wells match your search.</p>
        </section>

        <?php if ($isAdmin): ?>
          <section class="admin-notes" id="reports" aria-label="Administrator tools">
            <article class="panel"><h2>Reports</h2><p>Reporting tools will use verified sensor and field data.</p></article>
            <article class="panel" id="settings"><h2>Settings</h2><p>Manager permissions and alert rules will be configured here.</p></article>
          </section>
        <?php endif; ?>
      </main>
    </div>
  </div>
</body>
</html>
