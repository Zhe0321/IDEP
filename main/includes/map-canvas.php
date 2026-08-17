<?php
declare(strict_types=1);

$mapMode = $mapMode ?? 'large';
$mapClass = $mapMode === 'compact' ? ' map-stage--compact' : '';
?>
<div class="map-stage<?= $mapClass ?>" data-map-stage>
  <iframe
    class="google-map"
    src="https://www.google.com/maps?q=Bali%2C%20Indonesia&z=9&output=embed"
    title="Interactive Google map of Bali"
    loading="lazy"
    allowfullscreen
    referrerpolicy="no-referrer-when-downgrade"
  ></iframe>

  <div class="map-toolbar" aria-hidden="true">
    <span>Layers</span><strong>▱</strong>
  </div>

  <div class="map-markers" aria-label="Prototype recharge well locations">
    <?php foreach ($wells as $well): ?>
      <button
        class="map-marker map-marker--<?= htmlspecialchars($well['status']) ?>"
        type="button"
        style="--marker-x: <?= (int) $well['x'] ?>%; --marker-y: <?= (int) $well['y'] ?>%;"
        data-well='<?= htmlspecialchars(json_encode($well, JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?>'
        aria-label="View <?= htmlspecialchars($well['id']) ?> in <?= htmlspecialchars($well['city']) ?>"
        title="<?= htmlspecialchars($well['id']) ?> · <?= htmlspecialchars($well['statusLabel']) ?>"
      ></button>
    <?php endforeach; ?>
  </div>

  <article class="map-popover" data-map-popover hidden>
    <button class="map-popover__close" type="button" data-map-close aria-label="Close well details">×</button>
    <img data-popover-photo src="<?= htmlspecialchars($wellPhoto) ?>" alt="Recharge well placeholder">
    <div>
      <span class="map-popover__eyebrow" data-popover-name>Recharge Well</span>
      <h3><span data-popover-id>RW-01</span> · <span data-popover-city>Ubud</span></h3>
      <dl>
        <div><dt>Status</dt><dd data-popover-status>Online</dd></div>
        <div><dt>Volume</dt><dd data-popover-volume>20,933 L</dd></div>
        <div><dt>Absorption / inflow</dt><dd data-popover-inflow>4.0 m³/hr</dd></div>
        <div><dt>Last transmission</dt><dd data-popover-last>10 min ago</dd></div>
      </dl>
    </div>
  </article>
</div>
