<?php
declare(strict_types=1);

$selectedAlert = $alerts[0] ?? null;
?>
<section class="alerts-layout">
  <article class="panel alerts-list-panel">
    <h2>Active Alerts</h2>
    <div class="alerts-list">
      <?php if ($alerts === []): ?>
        <p class="alerts-empty">No active alerts. Connected sensors are transmitting normally.</p>
      <?php else: ?>
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
      <?php endif; ?>
    </div>
  </article>

  <aside class="panel alert-detail" data-alert-detail>
    <h2>Alert Detail</h2>
    <dl>
      <div><dt>Alert</dt><dd data-alert-field="alert"><?= htmlspecialchars((string) ($selectedAlert['alert'] ?? 'No active alerts')) ?></dd></div>
      <div><dt>Well ID</dt><dd data-alert-field="wellId"><?= htmlspecialchars((string) ($selectedAlert['wellId'] ?? '—')) ?></dd></div>
      <div><dt>Village</dt><dd data-alert-field="village"><?= htmlspecialchars((string) ($selectedAlert['village'] ?? '—')) ?></dd></div>
      <div><dt>Sensor Status</dt><dd data-alert-field="signal"><?= htmlspecialchars((string) ($selectedAlert['signal'] ?? 'Online')) ?></dd></div>
      <div><dt>Last Transmission</dt><dd data-alert-field="lastTransmission"><?= htmlspecialchars((string) ($selectedAlert['lastTransmission'] ?? '—')) ?></dd></div>
      <div><dt>Suggested action</dt><dd data-alert-field="action"><?= htmlspecialchars((string) ($selectedAlert['action'] ?? 'No action required')) ?></dd></div>
    </dl>
  </aside>
</section>
