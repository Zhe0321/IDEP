<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta
    name="description"
    content="Secure sign in for the Bali Water Protection groundwater monitoring portal."
  >
  <title>Sign in | Bali Water Protection</title>
  <link rel="icon" href="/images/brand/bwp-mark.png" type="image/png">
  <link rel="stylesheet" href="/main/css/login.css">
  <script src="/main/js/login.js" defer></script>
</head>
<body>
  <main class="auth-layout">
    <section class="auth-intro" aria-labelledby="intro-title">
      <a class="intro-logo" href="/" aria-label="Bali Water Protection home">
        <img src="/images/brand/bwp-logo-dark.png" alt="Bali Water Protection">
      </a>

      <div class="intro-content">
        <p class="intro-badge">IDEP FOUNDATION / BALI, INDONESIA</p>

        <div class="security-icon" aria-hidden="true">
          <span>✓</span>
        </div>

        <h1 id="intro-title">Secure access to<br>groundwater monitoring</h1>
        <p class="intro-description">
          Sign in to review sensor measurements, well status, alerts, and operational
          information for the Bali Water Protection programme.
        </p>

        <ul class="feature-list">
          <li>Role-based access for public and manager users</li>
          <li>Current well status and sensor transmissions</li>
          <li>Operational controls protected by authentication</li>
        </ul>
      </div>

      <p class="intro-footer">BALI WATER PROTECTION / IOT MONITORING PLATFORM</p>
    </section>

    <section class="auth-form-panel" aria-label="Sign in form">
      <div class="login-card">
        <p class="portal-label">SECURE MONITORING PORTAL</p>
        <h2>Sign in</h2>

        <form class="login-form" id="manager-login" action="/main/api/login.php" method="post">
          <div class="form-field">
            <label for="email">Email address</label>
            <input
              id="email"
              name="email"
              type="email"
              placeholder="name@organisation.org"
              autocomplete="username"
              required
            >
          </div>

          <div class="form-field">
            <label for="password">Password</label>
            <input
              id="password"
              name="password"
              type="password"
              placeholder="Enter your password"
              autocomplete="current-password"
              required
            >
          </div>

          <button class="primary-action" type="submit">Sign in as Manager</button>
          <p class="form-message" id="form-message" aria-live="polite"></p>
        </form>

        <div class="divider" aria-hidden="true"><span>OR</span></div>

        <a class="public-action" href="/index.php#impact">Continue as Public User</a>

        <aside class="permission-note">
          <strong>Permission check</strong>
          <p>Public users see three monitoring pages. Managers receive the full operational workspace.</p>
        </aside>

        <a class="back-link" href="/index.php#impact">Back to Impact</a>
      </div>
    </section>
  </main>
</body>
</html>
