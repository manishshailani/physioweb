<?php use App\Core\View; use App\Models\Patient; ?>
<h2 class="mb-1">Welcome, <?= View::e($current_patient['first_name'] ?? '') ?>!</h2>
<p class="text-muted mb-4">Patient ID: <?= View::e($current_patient['patient_code'] ?? '') ?></p>

<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="card p-3 text-center">
      <div class="text-muted small">Upcoming Appointments</div>
      <div class="fs-3 fw-bold"><?= count($upcomingAppointments) ?></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card p-3 text-center">
      <div class="text-muted small">Active Exercises</div>
      <div class="fs-3 fw-bold"><?= count($currentExercises) ?></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card p-3 text-center">
      <div class="text-muted small">Outstanding Balance</div>
      <div class="fs-3 fw-bold <?= $outstandingBalance > 0 ? 'text-danger' : 'text-success' ?>">&#8377;<?= number_format($outstandingBalance, 2) ?></div>
    </div>
  </div>
</div>

<?php if ($activeTreatmentPlan): ?>
<div class="card p-3 mb-4">
  <h6 class="text-uppercase text-muted small mb-2">Active Treatment Plan</h6>
  <p class="mb-1"><strong><?= View::e($activeTreatmentPlan['plan_number']) ?></strong> &mdash; <?= View::e($activeTreatmentPlan['diagnosis']) ?></p>
  <p class="text-muted small mb-0">Started <?= View::dateFormat($activeTreatmentPlan['start_date']) ?></p>
  <a href="/patient/treatment-plan" class="btn btn-sm btn-outline-primary mt-2 align-self-start">View Details</a>
</div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
        Upcoming Appointments
        <a href="/patient/appointments/book" class="btn btn-sm btn-primary">Book New</a>
      </div>
      <div class="list-group list-group-flush">
        <?php if (!$upcomingAppointments): ?>
          <div class="list-group-item text-muted">No upcoming appointments.</div>
        <?php endif; ?>
        <?php foreach ($upcomingAppointments as $a): ?>
          <div class="list-group-item d-flex justify-content-between align-items-center">
            <div>
              <div class="fw-semibold"><?= View::dateFormat($a['scheduled_date']) ?> at <?= View::e($a['scheduled_time']) ?></div>
              <div class="text-muted small"><?= View::e($a['service_name'] ?? 'General Consultation') ?> with <?= View::e($a['therapist_name'] ?? 'TBD') ?></div>
            </div>
            <span class="badge bg-light text-dark border"><?= View::e(ucfirst($a['status'])) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card">
      <div class="card-header fw-semibold">Current Exercises</div>
      <div class="list-group list-group-flush">
        <?php if (!$currentExercises): ?>
          <div class="list-group-item text-muted">No exercises assigned right now.</div>
        <?php endif; ?>
        <?php foreach (array_slice($currentExercises, 0, 5) as $ex): ?>
          <div class="list-group-item">
            <div class="fw-semibold"><?= View::e($ex['exercise_name']) ?></div>
            <div class="text-muted small"><?= View::e($ex['repetitions'] ?? '') ?> &mdash; <?= View::e($ex['frequency'] ?? '') ?></div>
          </div>
        <?php endforeach; ?>
      </div>
      <?php if ($currentExercises): ?>
        <div class="card-footer text-end"><a href="/patient/exercises" class="small">View all &rarr;</a></div>
      <?php endif; ?>
    </div>
  </div>
</div>
