<?php use App\Core\View; ?>
<section class="py-5">
  <div class="container">
    <h1 class="text-center mb-5">Our Services</h1>
    <div class="row g-4">
      <?php foreach ($services as $service): ?>
      <div class="col-md-4">
        <div class="card service-card p-3">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <h5 class="card-title mb-0"><?= View::e($service['name']) ?></h5>
              <?php if ((float) $service['fee'] > 0): ?>
              <span class="badge badge-fee">&#8377;<?= View::money($service['fee']) ?></span>
              <?php endif; ?>
            </div>
            <p class="card-text text-muted small"><?= View::e($service['short_description']) ?></p>
            <p class="text-muted small mb-2"><i class="fa-regular fa-clock me-1"></i><?= View::e($service['duration_minutes']) ?> min</p>
            <a href="/services/<?= View::e($service['slug']) ?>" class="btn btn-sm btn-outline-primary">Learn more</a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if (!$services): ?>
        <p class="text-center text-muted">Services will be listed here soon.</p>
      <?php endif; ?>
    </div>
  </div>
</section>
