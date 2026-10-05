<?php use App\Core\View; ?>
<h2 class="mb-4"><?= View::e($page_title) ?></h2>

<form method="post" action="/admin/packages/<?= (int) $package['id'] ?>" class="card p-4">
  <?= $csrf_field ?>
  <div class="row g-3">
    <div class="col-md-4">
      <label class="form-label">Name <span class="text-danger">*</span></label>
      <input type="text" name="name" class="form-control <?= isset($flash_errors['name']) ? 'is-invalid' : '' ?>" value="<?= View::e($old['name'] ?? $package['name']) ?>" required>
      <?php if (isset($flash_errors['name'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['name'][0]) ?></div><?php endif; ?>
    </div>
    <div class="col-md-2">
      <label class="form-label">Total Sessions <span class="text-danger">*</span></label>
      <input type="number" name="total_sessions" class="form-control <?= isset($flash_errors['total_sessions']) ? 'is-invalid' : '' ?>" value="<?= View::e($old['total_sessions'] ?? $package['total_sessions']) ?>" required>
      <?php if (isset($flash_errors['total_sessions'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['total_sessions'][0]) ?></div><?php endif; ?>
    </div>
    <div class="col-md-2">
      <label class="form-label">Price (&#8377;) <span class="text-danger">*</span></label>
      <input type="number" step="0.01" name="price" class="form-control <?= isset($flash_errors['price']) ? 'is-invalid' : '' ?>" value="<?= View::e($old['price'] ?? $package['price']) ?>" required>
      <?php if (isset($flash_errors['price'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['price'][0]) ?></div><?php endif; ?>
    </div>
    <div class="col-md-2">
      <label class="form-label">Discount %</label>
      <input type="number" step="0.01" name="discount_percent" class="form-control" value="<?= View::e($old['discount_percent'] ?? $package['discount_percent']) ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">Validity (days)</label>
      <input type="number" name="validity_days" class="form-control" value="<?= View::e($old['validity_days'] ?? $package['validity_days']) ?>">
    </div>
    <div class="col-12">
      <label class="form-label">Description</label>
      <textarea name="description" class="form-control" rows="3"><?= View::e($old['description'] ?? $package['description'] ?? '') ?></textarea>
    </div>
  </div>
  <div class="mt-3 d-flex gap-2">
    <button type="submit" class="btn btn-primary">Save Changes</button>
    <a href="/admin/packages" class="btn btn-outline-secondary">Cancel</a>
  </div>
</form>
