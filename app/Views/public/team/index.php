<?php use App\Core\View; ?>
<section class="py-5">
  <div class="container">
    <h1 class="text-center mb-5">Our Physiotherapists</h1>
    <div class="row g-4">
      <?php foreach ($therapists as $t): ?>
      <div class="col-md-4">
        <div class="card team-card text-center p-3">
          <div class="card-body">
            <i class="fa-solid fa-user-doctor fa-3x text-primary mb-3"></i>
            <h5><a class="text-decoration-none" href="/team/<?= View::e($t['slug']) ?>"><?= View::e($t['first_name'] . ' ' . $t['last_name']) ?></a></h5>
            <p class="text-muted small mb-1"><?= View::e($t['qualification']) ?></p>
            <p class="text-muted small"><?= View::e($t['specialization']) ?></p>
            <a href="/team/<?= View::e($t['slug']) ?>" class="btn btn-sm btn-outline-primary">View Profile</a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
