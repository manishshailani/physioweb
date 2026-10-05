<?php use App\Core\View; ?>
<h2 class="mb-4">Treatments Report</h2>
<?php View::partial('admin.reports._nav', ['active' => 'treatments', 'from' => $from, 'to' => $to]); ?>

<div class="row g-3 mb-4">
  <div class="col-md-12">
    <div class="card p-3">
      <div class="text-muted small mb-2">Plans Started in Period, by Status</div>
      <div class="row">
        <?php foreach ($statusCounts as $s): ?>
          <div class="col-auto mb-2">
            <div class="border rounded p-2 text-center" style="min-width:110px;">
              <div class="small text-muted"><?= View::e(ucfirst($s['status'])) ?></div>
              <div class="fw-semibold fs-5"><?= (int) $s['total'] ?></div>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (!$statusCounts): ?><div class="col-12 text-muted small">No treatment plans started in this period.</div><?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header fw-semibold">Treatment Plans Started: <?= View::dateFormat($from) ?> &ndash; <?= View::dateFormat($to) ?> (<?= count($plans) ?>)</div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr><th>Plan #</th><th>Patient</th><th>Therapist</th><th>Diagnosis</th><th>Start</th><th>End</th><th>Status</th><th>Sessions</th></tr></thead>
      <tbody>
        <?php if (!$plans): ?><tr><td colspan="8" class="text-center text-muted py-4">No treatment plans started in this period.</td></tr><?php endif; ?>
        <?php foreach ($plans as $p): ?>
          <tr>
            <td><?= View::e($p['plan_number']) ?></td>
            <td><?= View::e($p['patient_name']) ?></td>
            <td><?= View::e($p['therapist_name'] ?? 'Unassigned') ?></td>
            <td><?= View::e($p['diagnosis']) ?></td>
            <td><?= View::dateFormat($p['start_date']) ?></td>
            <td><?= $p['end_date'] ? View::dateFormat($p['end_date']) : '&mdash;' ?></td>
            <td><span class="badge bg-light text-dark border"><?= View::e(ucfirst($p['status'])) ?></span></td>
            <td><?= (int) $p['session_count'] ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
