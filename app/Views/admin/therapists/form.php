<?php use App\Core\View; ?>
<h2 class="mb-4"><?= View::e($page_title) ?></h2>

<form method="post" action="/admin/therapists/<?= (int) $therapist['id'] ?>" class="card p-4">
  <?= $csrf_field ?>
  <div class="mb-3"><strong>Slug:</strong> <code><?= View::e($therapist['slug']) ?></code> <span class="text-muted small">(not editable here)</span></div>
  <div class="row g-3">
    <div class="col-md-4">
      <label class="form-label">Session Length (min)</label>
      <input type="number" name="default_session_minutes" class="form-control" value="<?= View::e($old['default_session_minutes'] ?? $therapist['default_session_minutes']) ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">Display Order</label>
      <input type="number" name="display_order" class="form-control" value="<?= View::e($old['display_order'] ?? $therapist['display_order']) ?>">
    </div>
    <div class="col-md-4 d-flex align-items-end">
      <div class="form-check">
        <input type="checkbox" name="is_public" id="isPublic" class="form-check-input" value="1" <?= (int) ($old['is_public'] ?? $therapist['is_public']) === 1 ? 'checked' : '' ?>>
        <label class="form-check-label" for="isPublic">Show on public Team page</label>
      </div>
    </div>
    <div class="col-12">
      <label class="form-label">Bio</label>
      <textarea name="bio" class="form-control" rows="4"><?= View::e($old['bio'] ?? $therapist['bio'] ?? '') ?></textarea>
    </div>
  </div>
  <div class="mt-3 d-flex gap-2">
    <button type="submit" class="btn btn-primary">Save Changes</button>
    <a href="/admin/therapists" class="btn btn-outline-secondary">Cancel</a>
  </div>
</form>
