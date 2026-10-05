<?php use App\Core\View; ?>
<section class="hero text-center">
  <div class="container">
    <?php if (!empty($settings['clinic_offer'])): ?>
      <span class="badge badge-fee mb-3 px-3 py-2 fs-6"><?= View::e($settings['clinic_offer']) ?></span>
    <?php endif; ?>
    <h1 class="display-5"><?= View::e($settings['homepage_hero_title'] ?? 'Expert Physiotherapy Care') ?></h1>
    <p class="lead col-lg-8 mx-auto"><?= View::e($settings['homepage_hero_subtitle'] ?? '') ?></p>
    <a href="/book-appointment" class="btn btn-light btn-lg mt-2 fw-semibold">Book an Appointment</a>
  </div>
</section>

<section class="py-5 bg-soft">
  <div class="container">
    <h2 class="text-center mb-4">About <?= View::e($settings['clinic_name'] ?? '') ?></h2>
    <p class="col-lg-8 mx-auto text-center text-muted"><?= View::e($settings['homepage_about_text'] ?? '') ?></p>
  </div>
</section>

<section class="py-5">
  <div class="container">
    <h2 class="text-center mb-4">Our Services</h2>
    <div class="row g-4">
      <?php foreach ($services as $service): ?>
      <div class="col-md-4">
        <div class="card service-card p-3">
          <div class="card-body">
            <h5 class="card-title"><?= View::e($service['name']) ?></h5>
            <p class="card-text text-muted small"><?= View::e($service['short_description']) ?></p>
            <a href="/services/<?= View::e($service['slug']) ?>" class="btn btn-sm btn-outline-primary">Learn more</a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="text-center mt-4"><a href="/services" class="btn btn-primary">View All Services</a></div>
  </div>
</section>

<section class="py-5 bg-soft">
  <div class="container">
    <h2 class="text-center mb-4">Why Choose Us</h2>
    <div class="row g-4 text-center">
      <div class="col-md-3"><i class="fa-solid fa-user-doctor fa-2x text-primary mb-2"></i><h6>Certified Therapists</h6></div>
      <div class="col-md-3"><i class="fa-solid fa-chart-line fa-2x text-primary mb-2"></i><h6>Measurable Progress</h6></div>
      <div class="col-md-3"><i class="fa-solid fa-house-medical fa-2x text-primary mb-2"></i><h6>Home Visits Available</h6></div>
      <div class="col-md-3"><i class="fa-solid fa-hand-holding-heart fa-2x text-primary mb-2"></i><h6>Personalised Plans</h6></div>
    </div>
  </div>
</section>

<?php if ($therapists): ?>
<section class="py-5">
  <div class="container">
    <h2 class="text-center mb-4">Meet Our Physiotherapists</h2>
    <div class="row g-4">
      <?php foreach ($therapists as $t): ?>
      <div class="col-md-4">
        <div class="card team-card text-center p-3">
          <div class="card-body">
            <i class="fa-solid fa-user-doctor fa-3x text-primary mb-3"></i>
            <h5><?= View::e($t['first_name'] . ' ' . $t['last_name']) ?></h5>
            <p class="text-muted small mb-1"><?= View::e($t['qualification']) ?></p>
            <p class="text-muted small"><?= View::e($t['specialization']) ?></p>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($testimonials): ?>
<section class="py-5 bg-soft">
  <div class="container">
    <h2 class="text-center mb-4">What Our Patients Say</h2>
    <div class="row g-4">
      <?php foreach ($testimonials as $t): ?>
      <div class="col-md-4">
        <div class="testimonial-card p-4">
          <p class="mb-2">"<?= View::e($t['content']) ?>"</p>
          <strong>- <?= View::e($t['patient_name']) ?></strong>
          <div class="text-warning"><?= str_repeat('★', (int) $t['rating']) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="py-5 text-center">
  <div class="container">
    <h2>Ready to start your recovery?</h2>
    <a href="/book-appointment" class="btn btn-primary btn-lg mt-3">Book an Appointment</a>
  </div>
</section>
