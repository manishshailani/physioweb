<?php use App\Core\View; ?>
<article class="py-5">
  <div class="container" style="max-width:760px;">
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="/blog">Blog</a></li><li class="breadcrumb-item active"><?= View::e($post['title']) ?></li></ol></nav>
    <h1><?= View::e($post['title']) ?></h1>
    <p class="text-muted"><?= View::dateFormat($post['published_at']) ?></p>
    <?php if (!empty($post['featured_image'])): ?>
      <img src="<?= View::e($post['featured_image']) ?>" class="img-fluid rounded mb-4" alt="<?= View::e($post['title']) ?>">
    <?php endif; ?>
    <div class="blog-content">
      <?php
      // Content is stored as HTML authored by the admin CMS (e.g. a blog
      // rich-text field). It is rendered raw here deliberately -- it is
      // trusted admin-authored content, not user-submitted input. If a
      // rich-text editor allowing arbitrary HTML is added to the admin
      // Blog module, sanitize on save (see Services/HtmlSanitizer note in
      // docs/SECURITY.md) rather than removing this render.
      ?>
      <?= View::raw($post['content']) ?>
    </div>
  </div>
</article>
