<?php use App\Core\View; ?>
<section class="py-5">
  <div class="container" style="max-width:640px;">
    <h1 class="text-center mb-4">Book an Appointment</h1>
    <?php if (!empty($flash_success)): ?><div class="alert alert-success"><?= View::e($flash_success) ?></div><?php endif; ?>
    <div class="card p-4">
      <form method="post" action="/book-appointment">
        <?= $csrf_field ?>
        <div class="mb-3">
          <label class="form-label">Full Name *</label>
          <input type="text" name="name" class="form-control" value="<?= View::e($old['name'] ?? '') ?>" required>
          <?php if (!empty($flash_errors['name'])): ?><div class="text-danger small"><?= View::e($flash_errors['name'][0]) ?></div><?php endif; ?>
        </div>
        <div class="row">
          <div class="col-6 mb-3">
            <label class="form-label">Mobile *</label>
            <input type="text" name="mobile" class="form-control" value="<?= View::e($old['mobile'] ?? '') ?>" required>
            <?php if (!empty($flash_errors['mobile'])): ?><div class="text-danger small"><?= View::e($flash_errors['mobile'][0]) ?></div><?php endif; ?>
          </div>
          <div class="col-6 mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?= View::e($old['email'] ?? '') ?>">
          </div>
        </div>
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
            <label class="form-label">Preferred Date *</label>
            <input type="date" name="preferred_date" class="form-control" min="<?= date('Y-m-d') ?>" value="<?= View::e($old['preferred_date'] ?? '') ?>" required>
            <?php if (!empty($flash_errors['preferred_date'])): ?><div class="text-danger small"><?= View::e($flash_errors['preferred_date'][0]) ?></div><?php endif; ?>
          </div>
          <div class="col-6 mb-3">
            <label class="form-label">Preferred Time *</label>
            <input type="time" name="preferred_time" class="form-control" value="<?= View::e($old['preferred_time'] ?? '') ?>" required>
            <?php if (!empty($flash_errors['preferred_time'])): ?><div class="text-danger small"><?= View::e($flash_errors['preferred_time'][0]) ?></div><?php endif; ?>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Notes</label>
          <textarea name="notes" class="form-control" rows="3"><?= View::e($old['notes'] ?? '') ?></textarea>
        </div>
        <button class="btn btn-primary w-100" type="submit">Request Appointment</button>
      </form>
    </div>
  </div>
</section>
