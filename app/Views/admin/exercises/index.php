<?php use App\Core\View; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h2 class="mb-0">Exercise Library</h2>
</div>

<div class="card p-4 mb-4">
  <h6 class="text-uppercase text-muted small mb-3">Add Exercise</h6>
  <form method="post" action="/admin/exercises" class="row g-3">
    <?= $csrf_field ?>
    <div class="col-md-4">
      <label class="form-label">Name <span class="text-danger">*</span></label>
      <input type="text" name="name" class="form-control <?= isset($flash_errors['name']) ? 'is-invalid' : '' ?>" value="<?= View::e($old['name'] ?? '') ?>" required>
      <?php if (isset($flash_errors['name'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['name'][0]) ?></div><?php endif; ?>
    </div>
    <div class="col-md-3">
      <label class="form-label">Category</label>
      <input type="text" name="category" class="form-control" value="<?= View::e($old['category'] ?? '') ?>" placeholder="e.g. Mobility, Strength">
    </div>
    <div class="col-md-2">
      <label class="form-label">Default Reps</label>
      <input type="text" name="default_repetitions" class="form-control" value="<?= View::e($old['default_repetitions'] ?? '') ?>" placeholder="e.g. 3x10">
    </div>
    <div class="col-md-3">
      <label class="form-label">Default Frequency</label>
      <input type="text" name="default_frequency" class="form-control" value="<?= View::e($old['default_frequency'] ?? '') ?>" placeholder="e.g. Daily">
    </div>
    <div class="col-md-8">
      <label class="form-label">Instructions <span class="text-danger">*</span></label>
      <textarea name="instructions" class="form-control <?= isset($flash_errors['instructions']) ? 'is-invalid' : '' ?>" rows="2" required><?= View::e($old['instructions'] ?? '') ?></textarea>
      <?php if (isset($flash_errors['instructions'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['instructions'][0]) ?></div><?php endif; ?>
    </div>
    <div class="col-md-4">
      <label class="form-label">Video Link</label>
      <input type="url" name="video_link" class="form-control <?= isset($flash_errors['video_link']) ? 'is-invalid' : '' ?>" value="<?= View::e($old['video_link'] ?? '') ?>" placeholder="https://...">
      <?php if (isset($flash_errors['video_link'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['video_link'][0]) ?></div><?php endif; ?>
    </div>
    <div class="col-12">
      <button type="submit" class="btn btn-primary">Add to Library</button>
    </div>
  </form>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>Name</th><th>Category</th><th>Default Reps</th><th>Frequency</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
        <?php if (!$exercises): ?><tr><td colspan="6" class="text-center text-muted py-4">No exercises in the library yet.</td></tr><?php endif; ?>
        <?php foreach ($exercises as $e): ?>
          <tr>
            <td><?= View::e($e['name']) ?><?php if (!empty($e['video_link'])): ?> <a href="<?= View::e($e['video_link']) ?>" target="_blank" rel="noopener" title="Watch video"><i class="fa-solid fa-circle-play"></i></a><?php endif; ?></td>
            <td><?= View::e($e['category'] ?? '') ?></td>
            <td><?= View::e($e['default_repetitions'] ?? '') ?></td>
            <td><?= View::e($e['default_frequency'] ?? '') ?></td>
            <td><span class="badge bg-<?= $e['is_active'] ? 'success' : 'secondary' ?>"><?= $e['is_active'] ? 'Active' : 'Inactive' ?></span></td>
            <td class="text-end">
              <a href="/admin/exercises/<?= (int) $e['id'] ?>/edit" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="fa-solid fa-pen"></i></a>
              <?php if ($e['is_active']): ?>
              <form method="post" action="/admin/exercises/<?= (int) $e['id'] ?>/delete" class="d-inline" onsubmit="return confirm('Remove this exercise from the active library?');">
                <?= $csrf_field ?>
                <button type="submit" class="btn btn-sm btn-outline-danger" title="Deactivate"><i class="fa-solid fa-ban"></i></button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
