<?php
use App\Core\View;
use App\Models\Package;
?>
<h2 class="mb-4">Packages</h2>

<div class="card p-4 mb-4">
  <h6 class="text-uppercase text-muted small mb-3">Add Package</h6>
  <form method="post" action="/admin/packages" class="row g-3">
    <?= $csrf_field ?>
    <div class="col-md-4">
      <label class="form-label">Name <span class="text-danger">*</span></label>
      <input type="text" name="name" class="form-control <?= isset($flash_errors['name']) ? 'is-invalid' : '' ?>" value="<?= View::e($old['name'] ?? '') ?>" required>
      <?php if (isset($flash_errors['name'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['name'][0]) ?></div><?php endif; ?>
    </div>
    <div class="col-md-2">
      <label class="form-label">Total Sessions <span class="text-danger">*</span></label>
      <input type="number" name="total_sessions" class="form-control <?= isset($flash_errors['total_sessions']) ? 'is-invalid' : '' ?>" value="<?= View::e($old['total_sessions'] ?? '') ?>" required>
      <?php if (isset($flash_errors['total_sessions'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['total_sessions'][0]) ?></div><?php endif; ?>
    </div>
    <div class="col-md-2">
      <label class="form-label">Price (&#8377;) <span class="text-danger">*</span></label>
      <input type="number" step="0.01" name="price" class="form-control <?= isset($flash_errors['price']) ? 'is-invalid' : '' ?>" value="<?= View::e($old['price'] ?? '') ?>" required>
      <?php if (isset($flash_errors['price'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['price'][0]) ?></div><?php endif; ?>
    </div>
    <div class="col-md-2">
      <label class="form-label">Discount %</label>
      <input type="number" step="0.01" name="discount_percent" class="form-control" value="<?= View::e($old['discount_percent'] ?? '0') ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">Validity (days)</label>
      <input type="number" name="validity_days" class="form-control" value="<?= View::e($old['validity_days'] ?? '90') ?>">
    </div>
    <div class="col-12">
      <label class="form-label">Description</label>
      <textarea name="description" class="form-control" rows="2"><?= View::e($old['description'] ?? '') ?></textarea>
    </div>
    <div class="col-12">
      <button type="submit" class="btn btn-primary">Add Package</button>
    </div>
  </form>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>Name</th><th>Sessions</th><th>Price</th><th>Discounted Price</th><th>Validity</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
        <?php if (!$packages): ?><tr><td colspan="7" class="text-center text-muted py-4">No packages yet.</td></tr><?php endif; ?>
        <?php foreach ($packages as $p): ?>
          <tr>
            <td><?= View::e($p['name']) ?></td>
            <td><?= (int) $p['total_sessions'] ?></td>
            <td>&#8377;<?= View::money($p['price']) ?></td>
            <td>&#8377;<?= View::money(Package::discountedPrice($p)) ?><?php if ((float) $p['discount_percent'] > 0): ?> <span class="text-muted small">(<?= View::e($p['discount_percent']) ?>% off)</span><?php endif; ?></td>
            <td><?= (int) $p['validity_days'] ?> days</td>
            <td><span class="badge bg-<?= $p['is_active'] ? 'success' : 'secondary' ?>"><?= $p['is_active'] ? 'Active' : 'Inactive' ?></span></td>
            <td class="text-end">
              <a href="/admin/packages/<?= (int) $p['id'] ?>/edit" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-pen"></i></a>
              <?php if ($p['is_active']): ?>
              <form method="post" action="/admin/packages/<?= (int) $p['id'] ?>/delete" class="d-inline" onsubmit="return confirm('Deactivate this package?');">
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
