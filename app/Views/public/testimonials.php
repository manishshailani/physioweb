<?php use App\Core\View; ?>
<section class="py-5">
  <div class="container">
    <h1 class="text-center mb-5">Patient Testimonials</h1>
    <div class="row g-4">
      <?php foreach ($testimonials as $t): ?>
      <div class="col-md-4">
        <div class="testimonial-card p-4">
          <p class="mb-2">"<?= View::e($t['content']) ?>"</p>
          <strong><?= View::e($t['patient_name']) ?></strong>
          <div class="text-warning"><?= str_repeat('★', (int) $t['rating']) ?><span class="text-muted"><?= str_repeat('★', 5 - (int) $t['rating']) ?></span></div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if (!$testimonials): ?><p class="text-center text-muted">No testimonials yet.</p><?php endif; ?>
    </div>
  </div>
</section>
