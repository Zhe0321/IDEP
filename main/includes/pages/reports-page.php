<section class="operations-filter" aria-label="Report filters">
  <label><span>Report Period</span><select><option>Report Period</option><option>Last 7 Days</option><option>Last Month</option><option>Q2 2026</option></select></label>
  <label><span>Village</span><select><option>Village</option><option>Ubud</option><option>Tabanan</option><option>Denpasar</option></select></label>
  <label><span>Template</span><select><option>Template</option><option>Groundwater Summary</option><option>Sensor Status</option><option>Recharge Well Activity</option></select></label>
  <button class="operation-export" type="button" data-export-reports>CSV / Excel Export</button>
</section>

<section class="report-template-grid">
  <?php foreach (['Monthly Groundwater Summary', 'Sensor Status Report', 'Recharge Well Activity'] as $template): ?>
    <article class="panel report-template-card">
      <div><span class="report-icon">R</span><h2><?= htmlspecialchars($template) ?></h2></div>
      <p>Prepared for IDEP field monitoring and project review.</p>
      <button type="button" data-generate-report="<?= htmlspecialchars($template) ?>">Generate</button>
    </article>
  <?php endforeach; ?>
</section>

<section class="panel generated-reports-panel">
  <h2>Generated Reports</h2>
  <div class="table-scroll">
    <table class="operations-table">
      <thead><tr><th>Report Name</th><th>Period</th><th>Created By</th><th>Created Date</th><th>Status</th><th>Export</th></tr></thead>
      <tbody data-report-table>
        <?php foreach ($generatedReports as $report): ?>
          <tr>
            <td><?= htmlspecialchars($report['name']) ?></td><td><?= htmlspecialchars($report['period']) ?></td><td><?= htmlspecialchars($report['createdBy']) ?></td><td><?= htmlspecialchars($report['date']) ?></td><td><?= htmlspecialchars($report['status']) ?></td>
            <td><button class="export-pill" type="button" data-record-export="<?= htmlspecialchars($report['export']) ?>"><?= htmlspecialchars($report['export']) ?></button></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="operation-message" data-report-message hidden></p>
</section>
