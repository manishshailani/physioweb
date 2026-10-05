<?php use App\Core\View; ?>
<section class="py-5">
  <div class="container">
    <div class="row">
      <div class="col-lg-8">
        <h1 class="mb-4">Blog</h1>
        <div class="row g-4">
          <?php foreach ($posts as $post): ?>
          <div class="col-md-6">
            <div class="card blog-card p-3">
              <div class="card-body">
                <h5><a class="text-decoration-none" href="/blog/<?= View::e($post['slug']) ?>"><?= View::e($post['title']) ?></a></h5>
                <p class="text-muted small"><?= View::dateFormat($post['published_at']) ?></p>
                <p class="text-muted"><?= View::e($post['excerpt']) ?></p>
                <a href="/blog/<?= View::e($post['slug']) ?>" class="btn btn-sm btn-outline-primary">Read More</a>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
          <?php if (!$posts): ?><p class="text-muted">No posts found.</p><?php endif; ?>
        </div>
        <?php if ($pagination['last_page'] > 1): ?>
        <nav class="mt-4"><ul class="pagination justify-content-center">
          <?php for ($i = 1; $i <= $pagination['last_page']; $i++): ?>
            <li class="page-item <?= $i === $pagination['page'] ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a></li>
          <?php endfor; ?>
        </ul></nav>
        <?php endif; ?>
      </div>
      <div class="col-lg-4">
        <form method="get" action="/blog" class="mb-4">
          <div class="input-group"><input type="text" name="q" class="form-control" placeholder="Search articles" value="<?= View::e($search ?? '') ?>"><button class="btn btn-primary">Search</button></div>
        </form>
        <h6>Categories</h6>
        <ul class="list-unstyled">
          <?php foreach ($categories as $cat): ?>
            <li><a href="/blog/category/<?= View::e($cat['slug']) ?>"><?= View::e($cat['name']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>
</section>
