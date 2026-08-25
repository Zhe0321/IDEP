<?php
declare(strict_types=1);

$settingsSections = [
    ['key' => 'thresholds', 'label' => 'Sensor thresholds', 'title' => 'Groundwater Monitoring Settings'],
    ['key' => 'notifications', 'label' => 'Notification rules', 'title' => 'Notification Settings'],
    ['key' => 'quality', 'label' => 'Data quality checks', 'title' => 'Data Quality Settings'],
    ['key' => 'users', 'label' => 'User access', 'title' => 'User Access Settings'],
    ['key' => 'exports', 'label' => 'CSV / Excel defaults', 'title' => 'Export Settings'],
    ['key' => 'gis', 'label' => 'GIS layer settings', 'title' => 'GIS Layer Settings'],
];
?>
<section class="settings-layout">
  <aside class="panel settings-menu">
    <h2>Configuration</h2>
    <nav aria-label="Settings sections">
      <?php foreach ($settingsSections as $index => $section): ?>
        <button class="<?= $index === 0 ? 'is-selected' : '' ?>" type="button" data-settings-section="<?= htmlspecialchars($section['key']) ?>" data-settings-title="<?= htmlspecialchars($section['title']) ?>">
          <small><?= htmlspecialchars($section['label']) ?></small><strong><?= htmlspecialchars($section['label']) ?></strong>
        </button>
      <?php endforeach; ?>
    </nav>
  </aside>

  <form class="panel settings-form" data-settings-form>
    <h2 data-settings-heading>Groundwater Monitoring Settings</h2>
    <label><span>Water level alert threshold</span><input name="water_threshold" value="Prototype value"></label>
    <label><span>Missing transmission window</span><input name="transmission_window" value="Prototype value"></label>
    <label><span>Signal strength minimum</span><input name="signal_minimum" value="Prototype value"></label>
    <label><span>Data quality review rule</span><input name="quality_rule" value="Prototype value"></label>
    <label><span>Default export format</span><select name="export_format"><option>CSV and Excel</option><option>CSV</option><option>Excel</option></select></label>
    <div class="settings-actions"><button type="submit">Save prototype settings</button><span data-settings-message hidden>Settings saved in this browser session.</span></div>
  </form>
</section>
