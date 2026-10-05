<?php
use App\Core\View;
use App\Models\Staff;
?>
<h2 class="mb-4">Therapists</h2>
<p class="text-muted">Therapist profiles control what appears on the public website's Team page and which staff members can be scheduled for appointments.</p>

<?php if ($eligibleStaff): ?>
<div class="card p-4 mb-4">
  <h6 class="text-uppercase text-muted small mb-3">Create Public Therapist Profile</h6>
  <form method="post" action="/admin/therapists" class="row g-3">
    <?= $csrf_field ?>
    <div class="col-md-4">
      <label class="form-label">Staff Member <span class="text-danger">*</span></label>
      <select name="staff_id" class="form-select <?= isset($flash_errors['staff_id']) ? 'is-invalid' : '' ?>" required>
        <option value="">Select...</option>
        <?php foreach ($eligibleStaff as $s): ?>
          <option value="<?= (int) $s['id'] ?>"><?= View::e(Staff::fullName($s)) ?></option>
        <?php endforeach; ?>
      </select>
      <?php if (isset($flash_errors['staff_id'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['staff_id'][0]) ?></div><?php endif; ?>
    </div>
    <div class="col-md-4">
      <label class="form-label">Public URL Slug <span class="text-danger">*</span></label>
      <input type="text" name="slug" class="form-control <?= isset($flash_errors['slug']) ? 'is-invalid' : '' ?>" value="<?= View::e($old['slug'] ?? '') ?>" placeholder="e.g. dr-meenakshi" required>
      <?php if (isset($flash_errors['slug'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['slug'][0]) ?></div><?php endif; ?>
    </div>
    <div class="col-md-2">
      <label class="form-label">Session Length (min)</label>
      <input type="number" name="default_session_minutes" class="form-control" value="<?= View::e($old['default_session_minutes'] ?? '45') ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">Display Order</label>
      <input type="number" name="display_order" class="form-control" value="<?= View::e($old['display_order'] ?? '0') ?>">
    </div>
    <div class="col-12">
      <label class="form-label">Bio</label>
      <textarea name="bio" class="form-control" rows="2"><?= View::e($old['bio'] ?? '') ?></textarea>
    </div>
    <div class="col-12">
      <button type="submit" class="btn btn-primary">Create Profile</button>
    </div>
  </form>
</div>
<?php endif; ?>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>Name</th><th>Qualification</th><th>Slug</th><th>Session Length</th><th>Visibility</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
        <?php if (!$therapists): ?><tr><td colspan="6" class="text-center text-muted py-4">No therapist profiles yet.</td></tr><?php endif; ?>
        <?php foreach ($therapists as $t): ?>
          <tr>
            <td><?= View::e($t['first_name'] . ' ' . $t['last_name']) ?></td>
            <td><?= View::e($t['qualification'] ?? '') ?></td>
            <td><code><?= View::e($t['slug']) ?></code></td>
            <td><?= (int) $t['default_session_minutes'] ?> min</td>
            <td><span class="badge bg-<?= $t['is_public'] ? 'success' : 'secondary' ?>"><?= $t['is_public'] ? 'Public' : 'Hidden' ?></span></td>
            <td class="text-end">
              <a href="/admin/therapists/<?= (int) $t['id'] ?>/edit" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-pen"></i></a>
              <?php if ($t['is_public']): ?>
              <form method="post" action="/admin/therapists/<?= (int) $t['id'] ?>/delete" class="d-inline" onsubmit="return confirm('Hide this therapist from the public website?');">
                <?= $csrf_field ?>
                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-eye-slash"></i></button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
