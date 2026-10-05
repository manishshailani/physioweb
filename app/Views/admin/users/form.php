<?php use App\Core\View; ?>
<h2 class="mb-4"><?= View::e($page_title) ?></h2>

<form method="post" action="/admin/users/<?= (int) $editUser['id'] ?>" class="card p-4">
  <?= $csrf_field ?>
  <div class="row g-3">
    <div class="col-md-4">
      <label class="form-label">Name <span class="text-danger">*</span></label>
      <input type="text" name="name" class="form-control <?= isset($flash_errors['name']) ? 'is-invalid' : '' ?>" value="<?= View::e($old['name'] ?? $editUser['name']) ?>" required>
      <?php if (isset($flash_errors['name'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['name'][0]) ?></div><?php endif; ?>
    </div>
    <div class="col-md-4">
      <label class="form-label">Email <span class="text-danger">*</span></label>
      <input type="email" name="email" class="form-control <?= isset($flash_errors['email']) ? 'is-invalid' : '' ?>" value="<?= View::e($old['email'] ?? $editUser['email']) ?>" required>
      <?php if (isset($flash_errors['email'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['email'][0]) ?></div><?php endif; ?>
    </div>
    <div class="col-md-4">
      <label class="form-label">Role <span class="text-danger">*</span></label>
      <select name="role" class="form-select" required>
        <?php foreach ($roles as $r): ?>
          <option value="<?= $r ?>" <?= ($old['role'] ?? $editUser['role']) === $r ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $r)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-6">
      <label class="form-label">New Password <span class="text-muted">(leave blank to keep current)</span></label>
      <input type="password" name="password" class="form-control <?= isset($flash_errors['password']) ? 'is-invalid' : '' ?>" minlength="8">
      <?php if (isset($flash_errors['password'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['password'][0]) ?></div><?php endif; ?>
    </div>
    <div class="col-md-6">
      <label class="form-label">Linked Staff Record</label>
      <select name="staff_id" class="form-select">
        <option value="">None</option>
        <?php foreach ($staff as $s): ?>
          <option value="<?= (int) $s['id'] ?>" <?= (int) ($editUser['staff_id'] ?? 0) === (int) $s['id'] ? 'selected' : '' ?>><?= View::e($s['first_name'] . ' ' . $s['last_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="mt-3 d-flex gap-2">
    <button type="submit" class="btn btn-primary">Save Changes</button>
    <a href="/admin/users" class="btn btn-outline-secondary">Cancel</a>
  </div>
</form>
