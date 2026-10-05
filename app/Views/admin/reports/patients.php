<?php use App\Core\View; use App\Models\Patient; ?>
<h2 class="mb-4">Patients Report</h2>
<?php View::partial('admin.reports._nav', ['active' => 'patients', 'from' => $from, 'to' => $to]); ?>

<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="card p-3 text-center">
      <div class="text-muted small">New Registrations (period)</div>
      <div class="fs-3 fw-bold"><?= count($newPatients) ?></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card p-3 text-center">
      <div class="text-muted small">Total Active Patients</div>
      <div class="fs-3 fw-bold"><?= (int) $totalActive ?></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card p-3">
      <div class="text-muted small mb-1">Gender Distribution</div>
      <?php foreach ($genderDistribution as $g): ?>
        <div class="d-flex justify-content-between"><span><?= View::e(ucfirst($g['gender'])) ?></span><span class="fw-semibold"><?= (int) $g['total'] ?></span></div>
      <?php endforeach; ?>
      <?php if (!$genderDistribution): ?><span class="text-muted small">No active patients yet.</span><?php endif; ?>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card p-3">
      <div class="text-muted small mb-1">Age Groups</div>
      <?php foreach ($ageGroupDistribution as $bucket => $count): ?>
        <?php if ($count > 0): ?>
          <div class="d-flex justify-content-between"><span><?= View::e($bucket) ?></span><span class="fw-semibold"><?= (int) $count ?></span></div>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header fw-semibold">New Registrations: <?= View::dateFormat($from) ?> &ndash; <?= View::dateFormat($to) ?></div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr><th>Patient ID</th><th>Name</th><th>Gender</th><th>Age</th><th>Mobile</th><th>City</th><th>Registered On</th></tr></thead>
      <tbody>
        <?php if (!$newPatients): ?><tr><td colspan="7" class="text-center text-muted py-4">No patients registered in this period.</td></tr><?php endif; ?>
        <?php foreach ($newPatients as $p): ?>
          <tr>
            <td><?= View::e($p['patient_code']) ?></td>
            <td><?= View::e(Patient::fullName($p)) ?></td>
            <td><?= View::e(ucfirst($p['gender'])) ?></td>
            <td><?= Patient::age($p) ?? '&mdash;' ?></td>
            <td><?= View::e($p['mobile']) ?></td>
            <td><?= View::e($p['city'] ?? '') ?></td>
            <td><?= View::dateFormat($p['created_at'], 'd M Y') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
