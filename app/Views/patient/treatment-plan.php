<?php use App\Core\View; ?>
<h2 class="mb-4">My Treatment Plan</h2>

<?php if (!$plans): ?>
  <div class="alert alert-light border text-center text-muted">No treatment plans have been created for you yet.</div>
<?php endif; ?>

<?php if ($activePlan): ?>
<div class="card p-4 mb-4">
  <div class="d-flex justify-content-between align-items-start">
    <div>
      <h5 class="mb-1"><?= View::e($activePlan['plan_number']) ?> &mdash; <?= View::e($activePlan['diagnosis']) ?></h5>
      <p class="text-muted mb-0">Therapist: <?= View::e($activePlan['therapist_name'] ?? 'Unassigned') ?> &middot; Started <?= View::dateFormat($activePlan['start_date']) ?></p>
    </div>
    <span class="badge bg-success">Active</span>
  </div>
  <?php if (!empty($activePlan['notes'])): ?><p class="mt-3 mb-0"><?= View::e($activePlan['notes']) ?></p><?php endif; ?>
</div>

<?php if (count($sessions) >= 2): ?>
<div class="card p-3 mb-4">
  <h6 class="text-uppercase text-muted small mb-3">Pain &amp; Mobility Progress</h6>
  <canvas id="progressChart" height="90"></canvas>
</div>
<?php endif; ?>

<div class="card mb-4">
  <div class="card-header fw-semibold">Session History</div>
  <div class="table-responsive">
    <table class="table mb-0">
      <thead><tr><th>#</th><th>Date</th><th>Treatment Given</th><th>Pain Before</th><th>Pain After</th><th>Mobility</th></tr></thead>
      <tbody>
        <?php if (!$sessions): ?><tr><td colspan="6" class="text-center text-muted py-4">No sessions logged yet.</td></tr><?php endif; ?>
        <?php foreach ($sessions as $s): ?>
          <tr>
            <td><?= (int) $s['session_number'] ?></td>
            <td><?= View::dateFormat($s['session_date']) ?></td>
            <td><?= View::e($s['treatment_given']) ?></td>
            <td><?= $s['pain_score_before'] ?? '&mdash;' ?></td>
            <td><?= $s['pain_score_after'] ?? '&mdash;' ?></td>
            <td><?= $s['mobility_score'] ?? '&mdash;' ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php if ($assessments): ?>
<div class="card mb-4">
  <div class="card-header fw-semibold">Clinical Assessments</div>
  <div class="table-responsive">
    <table class="table mb-0">
      <thead><tr><th>Date</th><th>Pain Scale</th><th>Mobility</th><th>Recovery Status</th></tr></thead>
      <tbody>
        <?php foreach ($assessments as $a): ?>
          <tr>
            <td><?= View::dateFormat($a['assessment_date']) ?></td>
            <td><?= $a['pain_scale'] ?? '&mdash;' ?></td>
            <td><?= $a['mobility_score'] ?? '&mdash;' ?></td>
            <td><?= $a['recovery_status'] ? ucfirst($a['recovery_status']) : '&mdash;' ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php if ($plans): ?>
<div class="card">
  <div class="card-header fw-semibold">All Treatment Plans</div>
  <div class="table-responsive">
    <table class="table mb-0">
      <thead><tr><th>Plan #</th><th>Diagnosis</th><th>Therapist</th><th>Start</th><th>End</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($plans as $p): ?>
          <tr>
            <td><?= View::e($p['plan_number']) ?></td>
            <td><?= View::e($p['diagnosis']) ?></td>
            <td><?= View::e($p['therapist_name'] ?? 'Unassigned') ?></td>
            <td><?= View::dateFormat($p['start_date']) ?></td>
            <td><?= $p['end_date'] ? View::dateFormat($p['end_date']) : '&mdash;' ?></td>
            <td><span class="badge bg-light text-dark border"><?= View::e(ucfirst($p['status'])) ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php if ($activePlan && count($sessions) >= 2): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('progressChart'), {
  type: 'line',
  data: {
    labels: <?= json_encode(array_map(static fn ($s) => date('d M', strtotime($s['session_date'])), $sessions)) ?>,
    datasets: [
      { label: 'Pain Before', data: <?= json_encode(array_map(static fn ($s) => $s['pain_score_before'] !== null ? (int) $s['pain_score_before'] : null, $sessions)) ?>, borderColor: '#dc3545', backgroundColor: 'rgba(220,53,69,0.15)' },
      { label: 'Pain After', data: <?= json_encode(array_map(static fn ($s) => $s['pain_score_after'] !== null ? (int) $s['pain_score_after'] : null, $sessions)) ?>, borderColor: '#fd7e14', backgroundColor: 'rgba(253,126,20,0.15)' },
      { label: 'Mobility Score', data: <?= json_encode(array_map(static fn ($s) => $s['mobility_score'] !== null ? (int) $s['mobility_score'] : null, $sessions)) ?>, borderColor: '#198754', backgroundColor: 'rgba(25,135,84,0.15)' }
    ]
  },
  options: { responsive: true, scales: { y: { min: 0, max: 10 } } }
});
</script>
<?php endif; ?>
