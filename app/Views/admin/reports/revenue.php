<?php use App\Core\View; ?>
<h2 class="mb-4">Revenue Report</h2>
<?php View::partial('admin.reports._nav', ['active' => 'revenue', 'from' => $from, 'to' => $to]); ?>

<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="card p-3 text-center">
      <div class="text-muted small">Revenue Collected (period)</div>
      <div class="fs-4 fw-bold">&#8377;<?= number_format((float) $totalRevenue, 2) ?></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card p-3 text-center">
      <div class="text-muted small">Outstanding (all-time, as of today)</div>
      <div class="fs-4 fw-bold text-danger">&#8377;<?= number_format((float) $outstandingOverall, 2) ?></div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card p-3">
      <div class="text-muted small mb-2">By Payment Mode</div>
      <?php if (!$modeBreakdown): ?><span class="text-muted small">No payments in this period.</span><?php endif; ?>
      <?php foreach ($modeBreakdown as $m): ?>
        <div class="d-flex justify-content-between">
          <span><?= View::e(ucfirst(str_replace('_', ' ', $m['payment_mode']))) ?> (<?= (int) $m['count'] ?>)</span>
          <span class="fw-semibold">&#8377;<?= number_format((float) $m['total'], 2) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="card p-3 mb-4">
  <div class="text-muted small mb-2">Invoices Issued in Period, by Status</div>
  <div class="row">
    <?php foreach ($statusBreakdown as $s): ?>
      <div class="col-md-3 mb-2">
        <div class="border rounded p-2 text-center">
          <div class="small text-muted"><?= View::e(ucfirst(str_replace('_', ' ', $s['status']))) ?></div>
          <div class="fw-semibold"><?= (int) $s['count'] ?> inv. &mdash; &#8377;<?= number_format((float) $s['total'], 2) ?></div>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (!$statusBreakdown): ?><div class="col-12 text-muted small">No invoices issued in this period.</div><?php endif; ?>
  </div>
</div>

<?php if ($monthlyTrend): ?>
<div class="card p-3 mb-4">
  <div class="text-muted small mb-2">Monthly Trend</div>
  <div class="row">
    <?php foreach ($monthlyTrend as $row): ?>
      <div class="col-auto mb-2">
        <div class="border rounded p-2 text-center">
          <div class="small text-muted"><?= View::e($row['month']) ?></div>
          <div class="fw-semibold">&#8377;<?= number_format((float) $row['total'], 2) ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-header fw-semibold">Payments Received: <?= View::dateFormat($from) ?> &ndash; <?= View::dateFormat($to) ?> (<?= count($payments) ?>)</div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr><th>Date</th><th>Invoice #</th><th>Patient</th><th>Mode</th><th>Amount</th><th>Reference</th></tr></thead>
      <tbody>
        <?php if (!$payments): ?><tr><td colspan="6" class="text-center text-muted py-4">No payments received in this period.</td></tr><?php endif; ?>
        <?php foreach ($payments as $p): ?>
          <tr>
            <td><?= View::dateFormat($p['payment_date']) ?></td>
            <td><?= View::e($p['invoice_number']) ?></td>
            <td><?= View::e($p['patient_name']) ?></td>
            <td><?= View::e(ucfirst(str_replace('_', ' ', $p['payment_mode']))) ?></td>
            <td>&#8377;<?= number_format((float) $p['amount'], 2) ?></td>
            <td><?= View::e($p['reference_number'] ?? '') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
