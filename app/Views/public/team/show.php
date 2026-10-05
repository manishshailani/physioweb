<?php use App\Core\View; ?>
<section class="py-5">
  <div class="container">
    <div class="row">
      <div class="col-md-4 text-center">
        <i class="fa-solid fa-user-doctor fa-6x text-primary mb-3"></i>
        <h3><?= View::e($therapist['first_name'] . ' ' . $therapist['last_name']) ?></h3>
        <p class="text-muted"><?= View::e($therapist['qualification']) ?></p>
      </div>
      <div class="col-md-8">
        <h5>Specialization</h5>
        <p class="text-muted"><?= View::e($therapist['specialization']) ?></p>
        <h5>Experience</h5>
        <p class="text-muted"><?= View::e($therapist['experience_years']) ?> years</p>
        <h5>About</h5>
        <p class="text-muted"><?= View::raw(nl2br(View::e($therapist['bio']))) ?></p>
        <a href="/book-appointment" class="btn btn-primary">Book with <?= View::e($therapist['first_name']) ?></a>
      </div>
    </div>
  </div>
</section>
