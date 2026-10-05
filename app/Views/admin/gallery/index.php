<?php use App\Core\View; ?>
<h2 class="mb-4">Gallery</h2>

<div class="card p-4 mb-4">
  <h6 class="text-uppercase text-muted small mb-3">Upload Image</h6>
  <form method="post" action="/admin/gallery" enctype="multipart/form-data" class="row g-3">
    <?= $csrf_field ?>
    <div class="col-md-4">
      <label class="form-label">Image <span class="text-danger">*</span></label>
      <input type="file" name="media" class="form-control" accept="image/*" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Title</label>
      <input type="text" name="title" class="form-control">
    </div>
    <div class="col-md-3">
      <label class="form-label">Category</label>
      <select name="category" class="form-select">
        <?php foreach ($categories as $c): ?>
          <option value="<?= $c ?>"><?= ucfirst($c) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label">Order</label>
      <input type="number" name="display_order" class="form-control" value="0">
    </div>
    <div class="col-12">
      <button type="submit" class="btn btn-primary">Upload</button>
    </div>
  </form>
</div>

<div class="row g-3">
  <?php if (!$items): ?><p class="text-muted">No gallery images yet.</p><?php endif; ?>
  <?php foreach ($items as $item): ?>
    <div class="col-md-3">
      <div class="card">
        <img src="<?= View::e($item['file_path']) ?>" class="card-img-top" style="height:140px;object-fit:cover;">
        <div class="card-body p-2">
          <p class="small mb-1"><?= View::e($item['title'] ?? '') ?></p>
          <span class="badge bg-light text-dark border mb-2"><?= View::e($item['category']) ?></span>
          <form method="post" action="/admin/gallery/<?= (int) $item['id'] ?>/delete" onsubmit="return confirm('Delete this image?');">
            <?= $csrf_field ?>
            <button type="submit" class="btn btn-sm btn-outline-danger w-100"><i class="fa-solid fa-trash"></i> Delete</button>
          </form>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
