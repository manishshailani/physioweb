<?php
use App\Core\View;
$isEdit = $post !== null;
$action = $isEdit ? '/admin/blog/' . (int) $post['id'] : '/admin/blog';
$val = static function (string $field, $default = '') use ($old, $post) {
    if (array_key_exists($field, $old) && $old[$field] !== '') { return $old[$field]; }
    if ($post !== null && array_key_exists($field, $post) && $post[$field] !== null) { return $post[$field]; }
    return $default;
};
?>
<h2 class="mb-4"><?= View::e($page_title) ?></h2>

<form method="post" action="<?= View::e($action) ?>" enctype="multipart/form-data" class="card p-4">
  <?= $csrf_field ?>
  <div class="row g-3">
    <div class="col-md-8">
      <label class="form-label">Title <span class="text-danger">*</span></label>
      <input type="text" name="title" class="form-control <?= isset($flash_errors['title']) ? 'is-invalid' : '' ?>" value="<?= View::e($val('title')) ?>" required>
      <?php if (isset($flash_errors['title'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['title'][0]) ?></div><?php endif; ?>
    </div>
    <div class="col-md-4">
      <label class="form-label">Category</label>
      <select name="category_id" class="form-select">
        <option value="">&mdash; None &mdash;</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= (int) $c['id'] ?>" <?= (int) $val('category_id', 0) === (int) $c['id'] ? 'selected' : '' ?>><?= View::e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-6">
      <label class="form-label">Featured Image</label>
      <?php if ($isEdit && !empty($post['featured_image'])): ?><div class="mb-2"><img src="<?= View::e($post['featured_image']) ?>" height="60"></div><?php endif; ?>
      <input type="file" name="featured_image" class="form-control" accept="image/*">
    </div>
    <div class="col-md-3">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <option value="draft" <?= $val('status', 'draft') === 'draft' ? 'selected' : '' ?>>Draft</option>
        <option value="published" <?= $val('status', 'draft') === 'published' ? 'selected' : '' ?>>Published</option>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Tags</label>
      <input type="text" name="tags" class="form-control" value="<?= View::e($val('tags')) ?>" placeholder="comma, separated">
    </div>
    <div class="col-12">
      <label class="form-label">Excerpt</label>
      <textarea name="excerpt" class="form-control" rows="2"><?= View::e($val('excerpt')) ?></textarea>
    </div>
    <div class="col-12">
      <label class="form-label">Content <span class="text-danger">*</span></label>
      <textarea name="content" class="form-control <?= isset($flash_errors['content']) ? 'is-invalid' : '' ?>" rows="10" required><?= View::e($val('content')) ?></textarea>
      <?php if (isset($flash_errors['content'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['content'][0]) ?></div><?php endif; ?>
    </div>
    <div class="col-md-6">
      <label class="form-label">Meta Title (SEO)</label>
      <input type="text" name="meta_title" class="form-control" value="<?= View::e($val('meta_title')) ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Meta Description (SEO)</label>
      <input type="text" name="meta_description" class="form-control" value="<?= View::e($val('meta_description')) ?>">
    </div>
  </div>
  <div class="mt-3 d-flex gap-2">
    <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save Changes' : 'Create Post' ?></button>
    <a href="/admin/blog" class="btn btn-outline-secondary">Cancel</a>
  </div>
</form>
