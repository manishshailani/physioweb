<?php use App\Core\View; ?>
<h2 class="mb-4"><?= View::e($page_title) ?></h2>

<form method="post" action="/admin/exercises/<?= (int) $exercise['id'] ?>" class="card p-4">
  <?= $csrf_field ?>
  <div class="row g-3">
    <div class="col-md-5">
      <label class="form-label">Name <span class="text-danger">*</span></label>
      <input type="text" name="name" class="form-control <?= isset($flash_errors['name']) ? 'is-invalid' : '' ?>" value="<?= View::e($old['name'] ?? $exercise['name']) ?>" required>
      <?php if (isset($flash_errors['name'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['name'][0]) ?></div><?php endif; ?>
    </div>
    <div class="col-md-3">
      <label class="form-label">Category</label>
      <input type="text" name="category" class="form-control" value="<?= View::e($old['category'] ?? $exercise['category'] ?? '') ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">Default Reps</label>
      <input type="text" name="default_repetitions" class="form-control" value="<?= View::e($old['default_repetitions'] ?? $exercise['default_repetitions'] ?? '') ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">Default Frequency</label>
      <input type="text" name="default_frequency" class="form-control" value="<?= View::e($old['default_frequency'] ?? $exercise['default_frequency'] ?? '') ?>">
    </div>
    <div class="col-md-8">
      <label class="form-label">Instructions <span class="text-danger">*</span></label>
      <textarea name="instructions" class="form-control <?= isset($flash_errors['instructions']) ? 'is-invalid' : '' ?>" rows="3" required><?= View::e($old['instructions'] ?? $exercise['instructions']) ?></textarea>
      <?php if (isset($flash_errors['instructions'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['instructions'][0]) ?></div><?php endif; ?>
    </div>
    <div class="col-md-4">
      <label class="form-label">Video Link</label>
      <input type="url" name="video_link" class="form-control <?= isset($flash_errors['video_link']) ? 'is-invalid' : '' ?>" value="<?= View::e($old['video_link'] ?? $exercise['video_link'] ?? '') ?>">
      <?php if (isset($flash_errors['video_link'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['video_link'][0]) ?></div><?php endif; ?>
    </div>
  </div>
  <div class="mt-3 d-flex gap-2">
    <button type="submit" class="btn btn-primary">Save Changes</button>
    <a href="/admin/exercises" class="btn btn-outline-secondary">Cancel</a>
  </div>
</form>
