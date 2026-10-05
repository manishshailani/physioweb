<?php
use App\Core\View;
use App\Models\Patient;
?>
<div class="d-flex justify-content-between align-items-start mb-4">
  <div>
    <h2 class="mb-1">Treatment Plans</h2>
    <p class="text-muted mb-0"><?= View::e(Patient::fullName($patient)) ?> &middot; <?= View::e($patient['patient_code']) ?></p>
  </div>
  <a href="/admin/patients/<?= (int) $patient['id'] ?>" class="btn btn-outline-secondary btn-sm">&larr; Back to Patient</a>
</div>

<div class="card p-4 mb-4">
  <h6 class="text-uppercase text-muted small mb-3">New Treatment Plan</h6>
  <form method="post" action="/admin/patients/<?= (int) $patient['id'] ?>/treatment-plans" class="row g-3">
    <?= $csrf_field ?>
    <div class="col-md-5">
      <label class="form-label">Diagnosis <span class="text-danger">*</span></label>
      <input type="text" name="diagnosis" class="form-control <?= isset($flash_errors['diagnosis']) ? 'is-invalid' : '' ?>" value="<?= View::e($old['diagnosis'] ?? '') ?>" required>
      <?php if (isset($flash_errors['diagnosis'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['diagnosis'][0]) ?></div><?php endif; ?>
    </div>
    <div class="col-md-3">
      <label class="form-label">Therapist</label>
      <select name="therapist_id" class="form-select">
        <option value="">&mdash; Unassigned &mdash;</option>
        <?php foreach ($therapists as $t): ?>
          <option value="<?= (int) $t['id'] ?>"><?= View::e($t['first_name'] . ' ' . $t['last_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label">Start Date <span class="text-danger">*</span></label>
      <input type="date" name="start_date" class="form-control <?= isset($flash_errors['start_date']) ? 'is-invalid' : '' ?>" value="<?= View::e($old['start_date'] ?? date('Y-m-d')) ?>" required>
      <?php if (isset($flash_errors['start_date'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['start_date'][0]) ?></div><?php endif; ?>
    </div>
    <div class="col-md-2">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <option value="active">Active</option>
        <option value="completed">Completed</option>
        <option value="discontinued">Discontinued</option>
      </select>
    </div>
    <div class="col-12">
      <label class="form-label">Notes</label>
      <textarea name="notes" class="form-control" rows="2"><?= View::e($old['notes'] ?? '') ?></textarea>
    </div>
    <div class="col-12">
      <button type="submit" class="btn btn-primary">Create Plan</button>
    </div>
  </form>
</div>

<?php if (!$plans): ?>
  <p class="text-muted">No treatment plans yet for this patient.</p>
<?php endif; ?>

<?php foreach ($plans as $plan): ?>
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center bg-white">
    <div>
      <strong><?= View::e($plan['plan_number']) ?></strong> &middot; <?= View::e($plan['diagnosis']) ?>
      <span class="badge bg-<?= $plan['status'] === 'active' ? 'primary' : ($plan['status'] === 'completed' ? 'success' : 'secondary') ?> ms-2"><?= View::e($plan['status']) ?></span>
    </div>
    <div class="d-flex gap-2">
      <a href="/admin/treatment-plans/<?= (int) $plan['id'] ?>/edit" class="btn btn-sm btn-outline-secondary">Edit</a>
      <?php if ($plan['status'] !== 'discontinued'): ?>
      <form method="post" action="/admin/treatment-plans/<?= (int) $plan['id'] ?>/delete" onsubmit="return confirm('Discontinue this treatment plan?');">
        <?= $csrf_field ?>
        <button type="submit" class="btn btn-sm btn-outline-danger">Discontinue</button>
      </form>
      <?php endif; ?>
    </div>
  </div>
  <div class="card-body">
    <p class="text-muted small mb-3">
      Started <?= View::dateFormat($plan['start_date']) ?><?php if ($plan['end_date']): ?> &middot; Ended <?= View::dateFormat($plan['end_date']) ?><?php endif; ?>
      <?php if (!empty($plan['notes'])): ?><br><?= View::e($plan['notes']) ?><?php endif; ?>
    </p>

    <h6 class="text-uppercase text-muted small">Sessions</h6>
    <div class="table-responsive mb-3">
      <table class="table table-sm mb-0">
        <thead><tr><th>#</th><th>Date</th><th>Treatment</th><th>Duration</th><th>Pain Before/After</th><th>Mobility</th><th></th></tr></thead>
        <tbody>
          <?php $sessions = $sessionsByPlan[$plan['id']] ?? []; ?>
          <?php if (!$sessions): ?><tr><td colspan="7" class="text-center text-muted py-3">No sessions logged yet.</td></tr><?php endif; ?>
          <?php foreach ($sessions as $s): ?>
            <tr>
              <td><?= (int) $s['session_number'] ?></td>
              <td><?= View::dateFormat($s['session_date']) ?></td>
              <td><?= View::e($s['treatment_given']) ?></td>
              <td><?= (int) $s['duration_minutes'] ?> min</td>
              <td><?= $s['pain_score_before'] ?? '&mdash;' ?> / <?= $s['pain_score_after'] ?? '&mdash;' ?></td>
              <td><?= $s['mobility_score'] ?? '&mdash;' ?></td>
              <td>
                <form method="post" action="/admin/treatment-sessions/<?= (int) $s['id'] ?>/delete" onsubmit="return confirm('Delete this session record?');">
                  <?= $csrf_field ?>
                  <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <details>
      <summary class="small text-primary" style="cursor:pointer;">+ Log a new session</summary>
      <form method="post" action="/admin/treatment-plans/<?= (int) $plan['id'] ?>/sessions" class="row g-2 mt-2">
        <?= $csrf_field ?>
        <div class="col-md-2">
          <label class="form-label small">Date</label>
          <input type="date" name="session_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label small">Treatment Given</label>
          <input type="text" name="treatment_given" class="form-control form-control-sm" required>
        </div>
        <div class="col-md-1">
          <label class="form-label small">Mins</label>
          <input type="number" name="duration_minutes" class="form-control form-control-sm" value="30" min="5">
        </div>
        <div class="col-md-1">
          <label class="form-label small">Pain Before</label>
          <input type="number" name="pain_score_before" class="form-control form-control-sm" min="0" max="10">
        </div>
        <div class="col-md-1">
          <label class="form-label small">Pain After</label>
          <input type="number" name="pain_score_after" class="form-control form-control-sm" min="0" max="10">
        </div>
        <div class="col-md-1">
          <label class="form-label small">Mobility</label>
          <input type="number" name="mobility_score" class="form-control form-control-sm" min="0" max="10">
        </div>
        <div class="col-md-2">
          <label class="form-label small">Notes</label>
          <input type="text" name="progress_notes" class="form-control form-control-sm">
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-sm btn-primary">Save Session</button>
        </div>
      </form>
    </details>
  </div>
</div>
<?php endforeach; ?>
