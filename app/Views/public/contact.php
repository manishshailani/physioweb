<?php use App\Core\View; ?>
<section class="py-5">
  <div class="container">
    <h1 class="text-center mb-5">Contact Us</h1>
    <div class="row g-4">
      <div class="col-lg-6">
        <?php if (!empty($flash_success)): ?><div class="alert alert-success"><?= View::e($flash_success) ?></div><?php endif; ?>
        <form method="post" action="/contact">
          <?= $csrf_field ?>
          <div class="mb-3">
            <label class="form-label">Name *</label>
            <input type="text" name="name" class="form-control" value="<?= View::e($old['name'] ?? '') ?>" required>
            <?php if (!empty($flash_errors['name'])): ?><div class="text-danger small"><?= View::e($flash_errors['name'][0]) ?></div><?php endif; ?>
          </div>
          <div class="row">
            <div class="col-6 mb-3">
              <label class="form-label">Mobile</label>
              <input type="text" name="mobile" class="form-control" value="<?= View::e($old['mobile'] ?? '') ?>">
            </div>
            <div class="col-6 mb-3">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" value="<?= View::e($old['email'] ?? '') ?>">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Subject</label>
            <input type="text" name="subject" class="form-control" value="<?= View::e($old['subject'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Message *</label>
            <textarea name="message" rows="5" class="form-control" required><?= View::e($old['message'] ?? '') ?></textarea>
            <?php if (!empty($flash_errors['message'])): ?><div class="text-danger small"><?= View::e($flash_errors['message'][0]) ?></div><?php endif; ?>
          </div>
          <button class="btn btn-primary" type="submit">Send Message</button>
        </form>
      </div>
      <div class="col-lg-6">
        <div class="card p-4 h-100">
          <h5>Clinic Information</h5>
          <p><i class="fa-solid fa-location-dot text-primary me-2"></i><?= View::e($settings['clinic_address'] ?? '') ?></p>
          <p><i class="fa-solid fa-phone text-primary me-2"></i><?= View::e($settings['clinic_phone'] ?? '') ?></p>
          <p><i class="fa-solid fa-envelope text-primary me-2"></i><?= View::e($settings['clinic_email'] ?? '') ?></p>
        </div>
      </div>
    </div>
  </div>
</section>
