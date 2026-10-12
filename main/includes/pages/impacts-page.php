<?php
declare(strict_types=1);
?>
<section class="impact-video-panel" aria-labelledby="impact-video-title">
  <h2 class="sr-only" id="impact-video-title">How Bali Water Protection recharge wells work</h2>
  <iframe
    src="https://www.youtube.com/embed/pdlVbjnrzZk?rel=0"
    title="Bali Water Protection recharge well impact video"
    loading="eager"
    referrerpolicy="strict-origin-when-cross-origin"
    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
    allowfullscreen
  ></iframe>
</section>

<section class="impact-values" aria-labelledby="impact-values-title">
  <div class="impact-values__heading">
    <h2 id="impact-values-title">Our Water’s Lasting Values</h2>
    <p>Based on the attached impact analysis for <span data-programme-metric="well-count"><?= (int)$programmeMetrics['wellCount'] ?></span> recharge wells using peak-performing well assumptions.</p>
    <p>These figures are public-facing impact estimates, not operational sensor KPIs.</p>
  </div>

  <div class="impact-value-grid">
    <article class="impact-value-card">
      <span class="impact-value-card__label">People Supported</span>
      <p class="impact-value-card__metric"><strong data-programme-metric="people-supported"><?= htmlspecialchars((string)$programmeMetrics['peopleSupportedLabel']) ?></strong> People</p>
      <p class="impact-value-card__note">Equal to about <span data-programme-metric="families-supported"><?= htmlspecialchars((string)$programmeMetrics['familiesSupportedLabel']) ?></span> families</p>
      <div class="impact-value-card__rows">
        <p><span>Ubud</span><span>Testimonial or Figure</span></p>
        <p><span>City</span><span>Testimonial or Figure</span></p>
      </div>
    </article>

    <article class="impact-value-card">
      <span class="impact-value-card__label">Total Litres Absorbed</span>
      <p class="impact-value-card__metric"><strong data-programme-metric="actual-litres" title="<?= htmlspecialchars((string)$programmeMetrics['actualLitresExactLabel']) ?>"><?= htmlspecialchars((string)$programmeMetrics['actualLitresLabel']) ?></strong> Received by Sensors</p>
      <p class="impact-value-card__note">Cumulative valid sensor readings</p>
      <div class="impact-value-card__rows" data-programme-top-litres>
        <?php foreach ($programmeMetrics['topSensors'] as $sensor): ?>
          <p><span><?= htmlspecialchars((string)$sensor['name']) ?></span><span title="<?= htmlspecialchars((string)$sensor['litresExactLabel']) ?>"><?= htmlspecialchars((string)$sensor['litresLabel']) ?></span></p>
        <?php endforeach; ?>
      </div>
    </article>

    <article class="impact-value-card">
      <span class="impact-value-card__label">Total Wells</span>
      <p class="impact-value-card__metric"><strong data-programme-metric="well-count"><?= (int)$programmeMetrics['wellCount'] ?></strong> Recharge Wells</p>
      <p class="impact-value-card__note"><span data-programme-metric="village-count"><?= (int)$programmeMetrics['villageCount'] ?></span> Villages, <span data-programme-metric="district-count"><?= (int)$programmeMetrics['districtCount'] ?></span> Cities / Regencies</p>
      <div class="impact-value-card__rows" data-programme-top-wells>
        <?php foreach ($programmeMetrics['topDistricts'] as $district): ?>
          <p><span><?= htmlspecialchars((string)$district['name']) ?></span><span><?= (int)$district['wellCount'] ?></span></p>
        <?php endforeach; ?>
      </div>
    </article>
  </div>
</section>
