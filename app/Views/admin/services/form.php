<?php use App\Core\View; ?>
<h2 class="mb-4"><?= View::e($page_title) ?></h2>

<form method="post" action="/admin/services/<?= (int) $service['id'] ?>" class="card p-4">
  <?= $csrf_field ?>
  <div class="row g-3">
    <div class="col-md-5">
      <label class="form-label">Name <span class="text-danger">*</span></label>
      <input type="text" name="name" class="form-control <?= isset($flash_errors['name']) ? 'is-invalid' : '' ?>" value="<?= View::e($old['name'] ?? $service['name']) ?>" required>
      <?php if (isset($flash_errors['name'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['name'][0]) ?></div><?php endif; ?>
    </div>
    <div class="col-md-3">
      <label class="form-label">Category</label>
      <input type="text" name="category" class="form-control" value="<?= View::e($old['category'] ?? $service['category'] ?? '') ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">Fee (&#8377;) <span class="text-danger">*</span></label>
      <input type="number" step="0.01" name="fee" class="form-control <?= isset($flash_errors['fee']) ? 'is-invalid' : '' ?>" value="<?= View::e($old['fee'] ?? $service['fee']) ?>" required>
      <?php if (isset($flash_errors['fee'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['fee'][0]) ?></div><?php endif; ?>
    </div>
    <div class="col-md-2">
      <label class="form-label">GST %</label>
      <input type="number" step="0.01" name="gst_rate" class="form-control" value="<?= View::e($old['gst_rate'] ?? $service['gst_rate']) ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Duration (min)</label>
      <input type="number" name="duration_minutes" class="form-control" value="<?= View::e($old['duration_minutes'] ?? $service['duration_minutes']) ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Display Order</label>
      <input type="number" name="display_order" class="form-control" value="<?= View::e($old['display_order'] ?? $service['display_order']) ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Short Description</label>
      <input type="text" name="short_description" class="form-control" value="<?= View::e($old['short_description'] ?? $service['short_description'] ?? '') ?>">
    </div>
    <div class="col-12">
      <label class="form-label">Full Description</label>
      <textarea name="description" class="form-control" rows="3"><?= View::e($old['description'] ?? $service['description'] ?? '') ?></textarea>
    </div>
  </div>
  <div class="mt-3 d-flex gap-2">
    <button type="submit" class="btn btn-primary">Save Changes</button>
    <a href="/admin/services" class="btn btn-outline-secondary">Cancel</a>
  </div>
</form>
