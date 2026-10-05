<?php use App\Core\View; ?>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "MedicalTherapy",
  "name": <?= json_encode($service['name']) ?>,
  "description": <?= json_encode($service['short_description']) ?>
}
</script>
<section class="py-5">
  <div class="container">
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="/services">Services</a></li><li class="breadcrumb-item active"><?= View::e($service['name']) ?></li></ol></nav>
    <div class="row">
      <div class="col-lg-8">
        <h1><?= View::e($service['name']) ?></h1>
        <p class="text-muted"><i class="fa-regular fa-clock me-1"></i><?= View::e($service['duration_minutes']) ?> minutes
          <?php if ((float) $service['fee'] > 0): ?> &middot; <span class="fw-semibold">&#8377;<?= View::money($service['fee']) ?></span><?php endif; ?>
        </p>
        <div class="mt-4"><?= View::raw(nl2br(View::e($service['description'] ?: $service['short_description']))) ?></div>
      </div>
      <div class="col-lg-4">
        <div class="card p-3">
          <div class="card-body text-center">
            <h5>Ready to book?</h5>
            <a href="/book-appointment" class="btn btn-primary w-100">Book This Service</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
