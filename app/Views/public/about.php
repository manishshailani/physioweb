<?php use App\Core\View; ?>
<section class="py-5">
  <div class="container">
    <h1 class="mb-4">About <?= View::e($settings['clinic_name'] ?? '') ?></h1>
    <p class="lead text-muted"><?= View::e($settings['homepage_about_text'] ?? '') ?></p>

    <div class="row mt-5 g-4">
      <div class="col-md-6">
        <h4><i class="fa-solid fa-eye text-primary me-2"></i>Our Vision</h4>
        <p class="text-muted"><?= View::e($settings['clinic_vision'] ?? '') ?></p>
      </div>
      <div class="col-md-6">
        <h4><i class="fa-solid fa-bullseye text-primary me-2"></i>Our Mission</h4>
        <p class="text-muted"><?= View::e($settings['clinic_mission'] ?? '') ?></p>
      </div>
    </div>
  </div>
</section>

<?php if ($therapists): ?>
<section class="py-5 bg-soft">
  <div class="container">
    <h2 class="text-center mb-4">Our Physiotherapists</h2>
    <div class="row g-4">
      <?php foreach ($therapists as $t): ?>
      <div class="col-md-4">
        <div class="card team-card text-center p-3">
          <div class="card-body">
            <i class="fa-solid fa-user-doctor fa-3x text-primary mb-3"></i>
            <h5><a class="text-decoration-none" href="/team/<?= View::e($t['slug']) ?>"><?= View::e($t['first_name'] . ' ' . $t['last_name']) ?></a></h5>
            <p class="text-muted small mb-1"><?= View::e($t['qualification']) ?></p>
            <p class="text-muted small"><?= View::e($t['specialization']) ?> &middot; <?= View::e($t['experience_years']) ?> yrs experience</p>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
