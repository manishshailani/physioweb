<?php
use App\Core\View;
use App\Models\Patient;
?>
<div class="d-flex justify-content-between align-items-start mb-4">
  <div>
    <h2 class="mb-1"><?= View::e($page_title) ?></h2>
    <p class="text-muted mb-0"><?= View::e(Patient::fullName($patient)) ?> &middot; <?= View::e($patient['patient_code']) ?></p>
  </div>
  <a href="/admin/patients/<?= (int) $patient['id'] ?>/treatment-plans" class="btn btn-outline-secondary btn-sm">&larr; Back</a>
</div>

<form method="post" action="/admin/treatment-plans/<?= (int) $plan['id'] ?>" class="card p-4">
  <?= $csrf_field ?>
  <div class="row g-3">
    <div class="col-md-8">
      <label class="form-label">Diagnosis <span class="text-danger">*</span></label>
      <input type="text" name="diagnosis" class="form-control <?= isset($flash_errors['diagnosis']) ? 'is-invalid' : '' ?>" value="<?= View::e($old['diagnosis'] ?? $plan['diagnosis']) ?>" required>
      <?php if (isset($flash_errors['diagnosis'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['diagnosis'][0]) ?></div><?php endif; ?>
    </div>
    <div class="col-md-4">
      <label class="form-label">Therapist</label>
      <select name="therapist_id" class="form-select">
        <option value="">&mdash; Unassigned &mdash;</option>
        <?php foreach ($therapists as $t): ?>
          <option value="<?= (int) $t['id'] ?>" <?= (int) ($old['therapist_id'] ?? $plan['therapist_id'] ?? 0) === (int) $t['id'] ? 'selected' : '' ?>><?= View::e($t['first_name'] . ' ' . $t['last_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Start Date <span class="text-danger">*</span></label>
      <input type="date" name="start_date" class="form-control <?= isset($flash_errors['start_date']) ? 'is-invalid' : '' ?>" value="<?= View::e($old['start_date'] ?? $plan['start_date']) ?>" required>
      <?php if (isset($flash_errors['start_date'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['start_date'][0]) ?></div><?php endif; ?>
    </div>
    <div class="col-md-3">
      <label class="form-label">End Date</label>
      <input type="date" name="end_date" class="form-control" value="<?= View::e($old['end_date'] ?? $plan['end_date'] ?? '') ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <?php foreach (['active' => 'Active', 'completed' => 'Completed', 'discontinued' => 'Discontinued'] as $value => $label): ?>
          <option value="<?= $value ?>" <?= ($old['status'] ?? $plan['status']) === $value ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-12">
      <label class="form-label">Notes</label>
      <textarea name="notes" class="form-control" rows="3"><?= View::e($old['notes'] ?? $plan['notes'] ?? '') ?></textarea>
    </div>
  </div>
  <div class="mt-3 d-flex gap-2">
    <button type="submit" class="btn btn-primary">Save Changes</button>
    <a href="/admin/patients/<?= (int) $patient['id'] ?>/treatment-plans" class="btn btn-outline-secondary">Cancel</a>
  </div>
</form>
