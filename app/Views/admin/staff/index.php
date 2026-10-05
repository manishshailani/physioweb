<?php
use App\Core\View;
use App\Models\Staff;
?>
<h2 class="mb-4">Staff</h2>

<div class="card p-4 mb-4">
  <h6 class="text-uppercase text-muted small mb-3">Add Staff Member</h6>
  <form method="post" action="/admin/staff" enctype="multipart/form-data" class="row g-3">
    <?= $csrf_field ?>
    <div class="col-md-3">
      <label class="form-label">First Name <span class="text-danger">*</span></label>
      <input type="text" name="first_name" class="form-control <?= isset($flash_errors['first_name']) ? 'is-invalid' : '' ?>" value="<?= View::e($old['first_name'] ?? '') ?>" required>
      <?php if (isset($flash_errors['first_name'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['first_name'][0]) ?></div><?php endif; ?>
    </div>
    <div class="col-md-3">
      <label class="form-label">Last Name</label>
      <input type="text" name="last_name" class="form-control" value="<?= View::e($old['last_name'] ?? '') ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Role <span class="text-danger">*</span></label>
      <select name="role" class="form-select" required>
        <?php foreach ($roles as $r): ?>
          <option value="<?= $r ?>" <?= ($old['role'] ?? '') === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Photo</label>
      <input type="file" name="photo" class="form-control" accept="image/*">
    </div>
    <div class="col-md-3">
      <label class="form-label">Qualification</label>
      <input type="text" name="qualification" class="form-control" value="<?= View::e($old['qualification'] ?? '') ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Specialization</label>
      <input type="text" name="specialization" class="form-control" value="<?= View::e($old['specialization'] ?? '') ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">Experience (yrs)</label>
      <input type="number" step="0.5" name="experience_years" class="form-control" value="<?= View::e($old['experience_years'] ?? '') ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">Phone</label>
      <input type="text" name="phone" class="form-control" value="<?= View::e($old['phone'] ?? '') ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" value="<?= View::e($old['email'] ?? '') ?>">
    </div>
    <div class="col-12">
      <button type="submit" class="btn btn-primary">Add Staff Member</button>
    </div>
  </form>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th></th><th>Name</th><th>Role</th><th>Qualification</th><th>Phone</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
        <?php if (!$staff): ?><tr><td colspan="7" class="text-center text-muted py-4">No staff members yet.</td></tr><?php endif; ?>
        <?php foreach ($staff as $s): ?>
          <tr>
            <td><?php if (!empty($s['photo'])): ?><img src="<?= View::e($s['photo']) ?>" style="width:36px;height:36px;object-fit:cover;border-radius:50%;"><?php endif; ?></td>
            <td><?= View::e(Staff::fullName($s)) ?></td>
            <td class="text-capitalize"><?= View::e($s['role']) ?></td>
            <td><?= View::e($s['qualification'] ?? '') ?></td>
            <td><?= View::e($s['phone'] ?? '') ?></td>
            <td><span class="badge bg-<?= $s['is_active'] ? 'success' : 'secondary' ?>"><?= $s['is_active'] ? 'Active' : 'Inactive' ?></span></td>
            <td class="text-end">
              <a href="/admin/staff/<?= (int) $s['id'] ?>/edit" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-pen"></i></a>
              <?php if ($s['is_active']): ?>
              <form method="post" action="/admin/staff/<?= (int) $s['id'] ?>/delete" class="d-inline" onsubmit="return confirm('Deactivate this staff member?');">
                <?= $csrf_field ?>
                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-user-slash"></i></button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
