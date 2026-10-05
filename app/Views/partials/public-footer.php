<?php
use App\Core\View;
$settings = $settings ?? [];
$clinicName = $settings['clinic_name'] ?? 'PhysioWeb Clinic';
?>
<footer class="bg-dark text-light pt-5 pb-4 mt-5">
  <div class="container">
    <div class="row g-4">
      <div class="col-md-4">
        <h5><?= View::e($clinicName) ?></h5>
        <p class="text-secondary"><?= View::e($settings['clinic_tagline'] ?? '') ?></p>
        <p class="text-secondary mb-0"><?= View::e($settings['clinic_address'] ?? '') ?></p>
      </div>
      <div class="col-md-4">
        <h6>Quick Links</h6>
        <ul class="list-unstyled">
          <li><a class="text-secondary" href="/services">Services</a></li>
          <li><a class="text-secondary" href="/team">Our Physiotherapists</a></li>
          <li><a class="text-secondary" href="/blog">Blog</a></li>
          <li><a class="text-secondary" href="/book-appointment">Book an Appointment</a></li>
          <li><a class="text-secondary" href="/admin/login">Staff Login</a></li>
        </ul>
      </div>
      <div class="col-md-4">
        <h6>Contact</h6>
        <p class="text-secondary mb-1"><i class="fa-solid fa-phone me-2"></i><?= View::e($settings['clinic_phone'] ?? '') ?></p>
        <p class="text-secondary"><i class="fa-solid fa-envelope me-2"></i><?= View::e($settings['clinic_email'] ?? '') ?></p>
        <div>
          <?php if (!empty($settings['social_facebook'])): ?><a class="text-light me-3" href="<?= View::e($settings['social_facebook']) ?>"><i class="fa-brands fa-facebook fa-lg"></i></a><?php endif; ?>
          <?php if (!empty($settings['social_instagram'])): ?><a class="text-light" href="<?= View::e($settings['social_instagram']) ?>"><i class="fa-brands fa-instagram fa-lg"></i></a><?php endif; ?>
        </div>
      </div>
    </div>
    <hr class="border-secondary">
    <p class="text-secondary small mb-0">&copy; <?= date('Y') ?> <?= View::e($clinicName) ?>. All rights reserved.</p>
  </div>
</footer>
