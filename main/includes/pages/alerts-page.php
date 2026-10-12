<?php
declare(strict_types=1);

$selectedAlert = $alerts[0];
?>
<section class="alerts-layout">
  <article class="panel alerts-list-panel">
    <h2>Active Alerts</h2>
    <div class="alerts-list">
      <?php foreach ($alerts as $index => $alert): ?>
        <button
          class="alert-row<?= $index === 0 ? ' is-selected' : '' ?>"
          type="button"
          data-alert='<?= htmlspecialchars(json_encode($alert, JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?>'
          data-search="<?= strtolower(htmlspecialchars($alert['title'] . ' ' . $alert['wellId'] . ' ' . $alert['village'])) ?>"
        >
          <i class="alert-dot alert-dot--<?= htmlspecialchars($alert['severity']) ?>"></i>
          <span><strong><?= htmlspecialchars($alert['title']) ?></strong><small><?= htmlspecialchars($alert['wellId']) ?> · <?= htmlspecialchars($alert['village']) ?> · <?= htmlspecialchars($alert['summary']) ?></small></span>
          <em class="alert-state alert-state--<?= strtolower(htmlspecialchars($alert['state'])) ?>"><?= htmlspecialchars($alert['state']) ?></em>
        </button>
      <?php endforeach; ?>
    </div>
  </article>

  <aside class="panel alert-detail" data-alert-detail>
    <h2>Alert Detail</h2>
    <dl>
      <div><dt>Alert</dt><dd data-alert-field="alert"><?= htmlspecialchars($selectedAlert['alert']) ?></dd></div>
      <div><dt>Well ID</dt><dd data-alert-field="wellId"><?= htmlspecialchars($selectedAlert['wellId']) ?></dd></div>
      <div><dt>Village</dt><dd data-alert-field="village"><?= htmlspecialchars($selectedAlert['village']) ?></dd></div>
      <div><dt>Signal Strength</dt><dd data-alert-field="signal"><?= htmlspecialchars($selectedAlert['signal']) ?></dd></div>
      <div><dt>Last Transmission</dt><dd data-alert-field="lastTransmission"><?= htmlspecialchars($selectedAlert['lastTransmission']) ?></dd></div>
      <div><dt>Suggested action</dt><dd data-alert-field="action"><?= htmlspecialchars($selectedAlert['action']) ?></dd></div>
    </dl>
  </aside>
</section>
