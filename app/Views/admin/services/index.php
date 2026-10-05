<?php use App\Core\View; ?>
<h2 class="mb-4">Services</h2>

<div class="card p-4 mb-4">
  <h6 class="text-uppercase text-muted small mb-3">Add Service</h6>
  <form method="post" action="/admin/services" class="row g-3">
    <?= $csrf_field ?>
    <div class="col-md-4">
      <label class="form-label">Name <span class="text-danger">*</span></label>
      <input type="text" name="name" class="form-control <?= isset($flash_errors['name']) ? 'is-invalid' : '' ?>" value="<?= View::e($old['name'] ?? '') ?>" required>
      <?php if (isset($flash_errors['name'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['name'][0]) ?></div><?php endif; ?>
    </div>
    <div class="col-md-3">
      <label class="form-label">Category</label>
      <input type="text" name="category" class="form-control" value="<?= View::e($old['category'] ?? '') ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">Fee (&#8377;) <span class="text-danger">*</span></label>
      <input type="number" step="0.01" name="fee" class="form-control <?= isset($flash_errors['fee']) ? 'is-invalid' : '' ?>" value="<?= View::e($old['fee'] ?? '') ?>" required>
      <?php if (isset($flash_errors['fee'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['fee'][0]) ?></div><?php endif; ?>
    </div>
    <div class="col-md-1">
      <label class="form-label">GST %</label>
      <input type="number" step="0.01" name="gst_rate" class="form-control" value="<?= View::e($old['gst_rate'] ?? '18') ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">Duration (min)</label>
      <input type="number" name="duration_minutes" class="form-control" value="<?= View::e($old['duration_minutes'] ?? '45') ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Short Description</label>
      <input type="text" name="short_description" class="form-control" value="<?= View::e($old['short_description'] ?? '') ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Full Description</label>
      <textarea name="description" class="form-control" rows="1"><?= View::e($old['description'] ?? '') ?></textarea>
    </div>
    <div class="col-12">
      <button type="submit" class="btn btn-primary">Add Service</button>
    </div>
  </form>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>Name</th><th>Category</th><th>Fee</th><th>GST</th><th>Duration</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
        <?php if (!$services): ?><tr><td colspan="7" class="text-center text-muted py-4">No services yet.</td></tr><?php endif; ?>
        <?php foreach ($services as $s): ?>
          <tr>
            <td><?= View::e($s['name']) ?></td>
            <td><?= View::e($s['category'] ?? '') ?></td>
            <td>&#8377;<?= View::money($s['fee']) ?></td>
            <td><?= View::e($s['gst_rate']) ?>%</td>
            <td><?= (int) $s['duration_minutes'] ?> min</td>
            <td><span class="badge bg-<?= $s['is_active'] ? 'success' : 'secondary' ?>"><?= $s['is_active'] ? 'Active' : 'Inactive' ?></span></td>
            <td class="text-end">
              <a href="/admin/services/<?= (int) $s['id'] ?>/edit" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-pen"></i></a>
              <?php if ($s['is_active']): ?>
              <form method="post" action="/admin/services/<?= (int) $s['id'] ?>/delete" class="d-inline" onsubmit="return confirm('Deactivate this service?');">
                <?= $csrf_field ?>
                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-ban"></i></button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
