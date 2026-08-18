<?php
declare(strict_types=1);

$cities = array_values(array_unique(array_column($wells, 'city')));
sort($cities);
?>
<section class="operations-filter" aria-label="Historical data filters">
  <label>
    <span>Date Range</span>
    <select data-history-filter="period">
      <option value="all">Date Range</option>
      <option value="7">Last 7 Days</option>
      <option value="30">Last 30 Days</option>
      <option value="90">Last 3 Months</option>
    </select>
  </label>
  <label>
    <span>Village</span>
    <select data-history-filter="village">
      <option value="">Village</option>
      <?php foreach ($cities as $city): ?>
        <option value="<?= strtolower(htmlspecialchars($city)) ?>"><?= htmlspecialchars($city) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>
    <span>Well Type</span>
    <select data-history-filter="type">
      <option value="">Well Type</option>
      <option value="3">Type 3</option>
      <option value="4">Type 4</option>
    </select>
  </label>
  <button class="operation-export" type="button" data-export-measurements>CSV / Excel Export</button>
</section>

<section class="panel historical-chart-panel">
  <h2>Historical Measurements</h2>
  <div class="historical-chart-wrap">
    <canvas id="historical-chart" aria-label="Historical groundwater level chart"></canvas>
  </div>
</section>

<section class="panel records-panel">
  <h2>Measurement Records</h2>
  <div class="table-scroll">
    <table class="operations-table">
      <thead>
        <tr><th>Date</th><th>Well ID</th><th>Village</th><th>Water Level</th><th>Data Quality</th><th>Export</th></tr>
      </thead>
      <tbody>
        <?php foreach ($measurementRecords as $record): ?>
          <tr data-history-row data-village="<?= strtolower(htmlspecialchars($record['village'])) ?>" data-search="<?= strtolower(htmlspecialchars(implode(' ', $record))) ?>">
            <td><?= htmlspecialchars($record['date']) ?></td>
            <td><?= htmlspecialchars($record['wellId']) ?></td>
            <td><?= htmlspecialchars($record['village']) ?></td>
            <td><?= htmlspecialchars($record['waterLevel']) ?></td>
            <td><?= htmlspecialchars($record['quality']) ?></td>
            <td><button class="export-pill" type="button" data-record-export="<?= htmlspecialchars($record['export']) ?>"><?= htmlspecialchars($record['export']) ?></button></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
