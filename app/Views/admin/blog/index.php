<?php use App\Core\View; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h2 class="mb-0">Blog</h2>
  <div class="d-flex gap-2">
    <a href="/admin/blog-categories" class="btn btn-outline-secondary"><i class="fa-solid fa-tags me-1"></i> Categories</a>
    <a href="/admin/blog/create" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i> New Post</a>
  </div>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Published</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
        <?php if (!$posts): ?><tr><td colspan="5" class="text-center text-muted py-4">No blog posts yet.</td></tr><?php endif; ?>
        <?php foreach ($posts as $p): ?>
          <tr>
            <td><?= View::e($p['title']) ?></td>
            <td><?= View::e($p['category_name'] ?? '&mdash;') ?></td>
            <td><span class="badge bg-<?= $p['status'] === 'published' ? 'success' : 'secondary' ?>"><?= ucfirst($p['status']) ?></span></td>
            <td><?= $p['published_at'] ? View::dateFormat($p['published_at']) : '&mdash;' ?></td>
            <td class="text-end">
              <a href="/admin/blog/<?= (int) $p['id'] ?>/edit" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-pen"></i></a>
              <form method="post" action="/admin/blog/<?= (int) $p['id'] ?>/delete" class="d-inline" onsubmit="return confirm('Delete this post permanently?');">
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
