<?php use App\Core\View; ?>
<h2 class="mb-4">CMS Users</h2>

<div class="card p-4 mb-4">
  <h6 class="text-uppercase text-muted small mb-3">Add User</h6>
  <form method="post" action="/admin/users" class="row g-3">
    <?= $csrf_field ?>
    <div class="col-md-3">
      <label class="form-label">Name <span class="text-danger">*</span></label>
      <input type="text" name="name" class="form-control <?= isset($flash_errors['name']) ? 'is-invalid' : '' ?>" value="<?= View::e($old['name'] ?? '') ?>" required>
      <?php if (isset($flash_errors['name'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['name'][0]) ?></div><?php endif; ?>
    </div>
    <div class="col-md-3">
      <label class="form-label">Email <span class="text-danger">*</span></label>
      <input type="email" name="email" class="form-control <?= isset($flash_errors['email']) ? 'is-invalid' : '' ?>" value="<?= View::e($old['email'] ?? '') ?>" required>
      <?php if (isset($flash_errors['email'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['email'][0]) ?></div><?php endif; ?>
    </div>
    <div class="col-md-3">
      <label class="form-label">Password <span class="text-danger">*</span></label>
      <input type="password" name="password" class="form-control <?= isset($flash_errors['password']) ? 'is-invalid' : '' ?>" minlength="8" required>
      <?php if (isset($flash_errors['password'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['password'][0]) ?></div><?php endif; ?>
    </div>
    <div class="col-md-3">
      <label class="form-label">Role <span class="text-danger">*</span></label>
      <select name="role" class="form-select" required>
        <?php foreach ($roles as $r): ?>
          <option value="<?= $r ?>" <?= ($old['role'] ?? '') === $r ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $r)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label">Linked Staff Record (optional)</label>
      <select name="staff_id" class="form-select">
        <option value="">None</option>
        <?php foreach ($staff as $s): ?>
          <option value="<?= (int) $s['id'] ?>"><?= View::e($s['first_name'] . ' ' . $s['last_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-12">
      <button type="submit" class="btn btn-primary">Add User</button>
    </div>
  </form>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Last Login</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
        <?php if (!$users): ?><tr><td colspan="6" class="text-center text-muted py-4">No CMS users yet.</td></tr><?php endif; ?>
        <?php foreach ($users as $u): ?>
          <tr>
            <td><?= View::e($u['name']) ?><?php if ((int) $u['id'] === (int) ($current_admin['id'] ?? 0)): ?> <span class="badge bg-info">You</span><?php endif; ?></td>
            <td><?= View::e($u['email']) ?></td>
            <td class="text-capitalize"><?= View::e(str_replace('_', ' ', $u['role'])) ?></td>
            <td><?= $u['last_login_at'] ? View::dateFormat($u['last_login_at'], 'd M Y H:i') : 'Never' ?></td>
            <td><span class="badge bg-<?= $u['is_active'] ? 'success' : 'secondary' ?>"><?= $u['is_active'] ? 'Active' : 'Inactive' ?></span></td>
            <td class="text-end">
              <a href="/admin/users/<?= (int) $u['id'] ?>/edit" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-pen"></i></a>
              <?php if ($u['is_active'] && (int) $u['id'] !== (int) ($current_admin['id'] ?? 0)): ?>
              <form method="post" action="/admin/users/<?= (int) $u['id'] ?>/delete" class="d-inline" onsubmit="return confirm('Deactivate this user?');">
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
