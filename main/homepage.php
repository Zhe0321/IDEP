<?php
declare(strict_types=1);

$homepageCssVersion = (string) filemtime(__DIR__ . '/css/homepage.css');
$homepageJsVersion = (string) filemtime(__DIR__ . '/js/homepage.js');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta
    name="description"
    content="Restoring Bali's groundwater resilience through recharge wells, local stewardship and transparent environmental monitoring."
  >
  <title>Bali Water Protection | IDEP Foundation</title>
  <link rel="icon" href="/images/brand/bwp-mark.png" type="image/png">
  <link rel="stylesheet" href="/main/css/homepage.css?v=<?= $homepageCssVersion ?>">
  <script src="/main/js/homepage.js?v=<?= $homepageJsVersion ?>" defer></script>
</head>
<body>
  <header class="site-header">
    <div class="page-shell header-inner">
      <a class="brand" href="#top" aria-label="Bali Water Protection home">
        <img src="/images/brand/idep-bwp-logo-green.png" alt="IDEP Foundation and Bali Water Protection">
      </a>

      <nav class="main-nav" aria-label="Main navigation">
        <a class="button button-primary button-small" href="https://www.globalgiving.org/donate/72769/idep-foundation/" target="_blank" rel="noopener">Donate</a>
        <a class="button button-primary button-small" href="/main/login.php">Login</a>
      </nav>
    </div>
  </header>

  <main>
    <section class="hero" id="top">
      <div class="page-shell hero-inner">
        <div class="hero-copy">
          <h1>Bali Water Protection</h1>
          <p class="hero-lead">
            Restoring groundwater resilience through recharge wells, local stewardship,
            and transparent environmental monitoring.
          </p>
          <p class="hero-description">
            The programme helps return rainwater back into Bali’s aquifers, reducing flood
            pressure while strengthening water security for communities across the island.
          </p>

          <div class="hero-actions">
            <a class="button button-primary" href="/main/public-dashboard.php">View Dashboard</a>
            <a class="button button-secondary" href="/main/public-impacts.php">See Impact</a>
          </div>
        </div>

        <div class="photo-single" aria-label="Bali Water Protection programme photo">
          <figure class="photo">
            <img
              src="/images/homepageIMG/water-culture-bali.jpg"
              alt="Water as part of Balinese culture"
            >
          </figure>
        </div>
      </div>
    </section>

    <section class="content-section impact-section" id="impact">
      <div class="page-shell">
        <div class="section-heading reveal">
          <h2>Our Water’s Lasting Values</h2>
          <p>Based on the attached impact analysis for 76 recharge wells using peak-performing well assumptions.</p>
          <p>These figures are public-facing impact estimates, not operational sensor KPIs.</p>
        </div>

        <div class="card-grid impact-grid">
          <article class="impact-card reveal">
            <span class="card-label">People Supported</span>
            <p class="feature-metric"><strong>~498K</strong> <span>People</span></p>
            <p class="card-note">Equal to about 124K families</p>
            <div class="card-lines card-lines--rows">
              <p><span>Ubud</span><span>Testimonial or Figure</span></p>
              <p><span>City</span><span>Testimonial or Figure</span></p>
            </div>
          </article>

          <article class="impact-card reveal">
            <span class="card-label">Total Litres Absorbed</span>
            <p class="feature-metric"><strong>9.1B L</strong> <span>Restored Annually</span></p>
            <p class="card-note">Estimated groundwater recharge</p>
            <div class="card-lines card-lines--rows">
              <p><span>Ubud</span><span>500L Restored</span></p>
              <p><span>City</span><span>1000L Restored</span></p>
            </div>
          </article>

          <article class="impact-card reveal">
            <span class="card-label">Total Wells</span>
            <p class="feature-metric"><strong>76</strong> <span>Recharge Wells</span></p>
            <p class="card-note">XX Villages, XX Cities</p>
            <div class="card-lines card-lines--rows">
              <p><span>Ubud</span><span>23</span></p>
              <p><span>Jembrana</span><span>5</span></p>
              <p><span>Penglipuran</span><span>2</span></p>
            </div>
          </article>
        </div>
      </div>
    </section>

    <section class="content-section sustainability-section" id="sustainability">
      <div class="page-shell">
        <div class="section-heading reveal">
          <h2>IDEP’s Sustainability Passion</h2>
          <p>Based on the attached impact analysis for 76 recharge wells using peak-performing well assumptions.</p>
          <p>These figures are public-facing impact estimates, not operational sensor KPIs.</p>
        </div>

        <div class="card-grid information-grid">
          <article class="information-card reveal">
            <h3>Why Build Recharge Wells?</h3>
            <div class="information-body">
              Recharge wells actively return rainwater into underground aquifers. They support
              groundwater reserves, reduce local flooding, and create measurable benefits that can be
              translated into community water security metrics.
            </div>
            <a class="button button-primary button-small card-cta" href="/main/public-impacts.php">Learn More</a>
          </article>

          <article class="information-card reveal">
            <h3>Testimonies</h3>
            <div class="information-body testimonies">
              <span>Sarah&nbsp; ★★★★★ Penglipuran, Bali</span>
              <span>PLACEHOLDER TEXT</span>
              <br>
              <span>Komong&nbsp; ★★★★★ Ubud, Bali</span>
              <span>PLACEHOLDER TEXT</span>
              <br>
              <span>Ketuk&nbsp; ★★★★★ Jembraya, Bali</span>
              <span>PLACEHOLDER TEXT</span>
            </div>
            <a class="read-more" href="/main/login.php">Read more <span class="arrow" aria-hidden="true"></span></a>
          </article>
        </div>
      </div>
    </section>
  </main>
</body>
</html>
