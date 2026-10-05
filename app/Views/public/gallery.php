<?php use App\Core\View; ?>
<section class="py-5">
  <div class="container">
    <h1 class="text-center mb-4">Gallery</h1>
    <div class="d-flex justify-content-center gap-2 mb-4">
      <?php foreach (['all' => 'All', 'clinic' => 'Clinic', 'treatments' => 'Treatments', 'events' => 'Events'] as $key => $label): ?>
        <a href="/gallery?category=<?= $key ?>" class="btn btn-sm <?= $activeCategory === $key ? 'btn-primary' : 'btn-outline-primary' ?>"><?= $label ?></a>
      <?php endforeach; ?>
    </div>
    <div class="row g-3">
      <?php foreach ($items as $item): ?>
      <div class="col-md-3 col-6">
        <?php if ($item['media_type'] === 'image'): ?>
          <img src="<?= View::e($item['file_path']) ?>" class="gallery-thumb" alt="<?= View::e($item['title']) ?>">
        <?php else: ?>
          <video class="gallery-thumb" controls src="<?= View::e($item['file_path']) ?>"></video>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
      <?php if (!$items): ?><p class="text-center text-muted">No items in this category yet.</p><?php endif; ?>
    </div>
  </div>
</section>
