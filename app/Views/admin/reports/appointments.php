<?php use App\Core\View; ?>
<h2 class="mb-4">Appointments Report</h2>
<?php View::partial('admin.reports._nav', ['active' => 'appointments', 'from' => $from, 'to' => $to]); ?>

<div class="row g-3 mb-4">
  <div class="col-md-6">
    <div class="card p-3">
      <div class="text-muted small mb-2">By Status</div>
      <?php if (!$statusCounts): ?><span class="text-muted small">No appointments in this period.</span><?php endif; ?>
      <?php foreach ($statusCounts as $s): ?>
        <div class="d-flex justify-content-between"><span><?= View::e(ucfirst($s['status'])) ?></span><span class="fw-semibold"><?= (int) $s['total'] ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card p-3">
      <div class="text-muted small mb-2">By Therapist</div>
      <?php if (!$byTherapist): ?><span class="text-muted small">No appointments in this period.</span><?php endif; ?>
      <?php foreach ($byTherapist as $t): ?>
        <div class="d-flex justify-content-between"><span><?= View::e($t['therapist_name']) ?></span><span class="fw-semibold"><?= (int) $t['total'] ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header fw-semibold">Appointments: <?= View::dateFormat($from) ?> &ndash; <?= View::dateFormat($to) ?> (<?= count($appointments) ?>)</div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr><th>Appt #</th><th>Patient</th><th>Therapist</th><th>Service</th><th>Date</th><th>Time</th><th>Status</th></tr></thead>
      <tbody>
        <?php if (!$appointments): ?><tr><td colspan="7" class="text-center text-muted py-4">No appointments in this period.</td></tr><?php endif; ?>
        <?php foreach ($appointments as $a): ?>
          <tr>
            <td><?= View::e($a['appointment_number']) ?></td>
            <td><?= View::e($a['patient_name'] ?? '') ?></td>
            <td><?= View::e($a['therapist_name'] ?? 'Unassigned') ?></td>
            <td><?= View::e($a['service_name'] ?? '') ?></td>
            <td><?= View::dateFormat($a['scheduled_date']) ?></td>
            <td><?= View::e($a['scheduled_time']) ?></td>
            <td><span class="badge bg-light text-dark border"><?= View::e(ucfirst($a['status'])) ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
