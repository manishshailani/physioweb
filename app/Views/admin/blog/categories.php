<?php use App\Core\View; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h2 class="mb-0">Blog Categories</h2>
  <a href="/admin/blog" class="btn btn-outline-secondary btn-sm">&larr; Back to Posts</a>
</div>

<div class="card p-4 mb-4">
  <form method="post" action="/admin/blog-categories" class="row g-2">
    <?= $csrf_field ?>
    <div class="col-md-6">
      <input type="text" name="name" class="form-control" placeholder="New category name" required>
    </div>
    <div class="col-md-3">
      <button type="submit" class="btn btn-primary">Add Category</button>
    </div>
  </form>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table mb-0">
      <thead><tr><th>Name</th><th>Slug</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
        <?php if (!$categories): ?><tr><td colspan="3" class="text-center text-muted py-4">No categories yet.</td></tr><?php endif; ?>
        <?php foreach ($categories as $c): ?>
          <tr>
            <td><?= View::e($c['name']) ?></td>
            <td><code><?= View::e($c['slug']) ?></code></td>
            <td class="text-end">
              <form method="post" action="/admin/blog-categories/<?= (int) $c['id'] ?>/delete" onsubmit="return confirm('Delete this category? Posts in it will become uncategorized.');">
                <?= $csrf_field ?>
                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
