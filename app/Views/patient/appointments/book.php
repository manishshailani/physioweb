<?php use App\Core\View; ?>
<h2 class="mb-4">Book an Appointment</h2>

<div class="card p-4" style="max-width:640px;">
  <form method="post" action="/patient/appointments">
    <?= $csrf_field ?>
    <div class="mb-3">
      <label class="form-label">Service Required</label>
      <select name="service_id" class="form-select">
        <option value="">Select a service (optional)</option>
        <?php foreach ($services as $s): ?>
          <option value="<?= $s['id'] ?>" <?= (string) ($old['service_id'] ?? '') === (string) $s['id'] ? 'selected' : '' ?>><?= View::e($s['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Preferred Physiotherapist</label>
      <select name="therapist_id" class="form-select">
        <option value="">No preference</option>
        <?php foreach ($therapists as $t): ?>
          <option value="<?= $t['id'] ?>" <?= (string) ($old['therapist_id'] ?? '') === (string) $t['id'] ? 'selected' : '' ?>><?= View::e($t['first_name'] . ' ' . $t['last_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="row">
      <div class="col-6 mb-3">
        <label class="form-label">Preferred Date <span class="text-danger">*</span></label>
        <input type="date" name="preferred_date" class="form-control <?= isset($flash_errors['preferred_date']) ? 'is-invalid' : '' ?>" min="<?= date('Y-m-d') ?>" value="<?= View::e($old['preferred_date'] ?? '') ?>" required>
        <?php if (!empty($flash_errors['preferred_date'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['preferred_date'][0]) ?></div><?php endif; ?>
      </div>
      <div class="col-6 mb-3">
        <label class="form-label">Preferred Time <span class="text-danger">*</span></label>
        <input type="time" name="preferred_time" class="form-control <?= isset($flash_errors['preferred_time']) ? 'is-invalid' : '' ?>" value="<?= View::e($old['preferred_time'] ?? '') ?>" required>
        <?php if (!empty($flash_errors['preferred_time'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['preferred_time'][0]) ?></div><?php endif; ?>
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label">Notes for the Therapist</label>
      <textarea name="notes" class="form-control" rows="3"><?= View::e($old['notes'] ?? '') ?></textarea>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-primary" type="submit">Request Appointment</button>
      <a href="/patient/appointments" class="btn btn-outline-secondary">Cancel</a>
    </div>
  </form>
</div>
