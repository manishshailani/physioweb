<?php use App\Core\View; ?>
<h2 class="mb-4">Site Settings</h2>

<form method="post" action="/admin/settings" enctype="multipart/form-data">
  <?= $csrf_field ?>

  <div class="card p-4 mb-3">
    <h6 class="text-uppercase text-muted small mb-3">Clinic Information</h6>
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Clinic Name</label>
        <input type="text" name="clinic_name" class="form-control" value="<?= View::e($settings['clinic_name'] ?? '') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Tagline</label>
        <input type="text" name="clinic_tagline" class="form-control" value="<?= View::e($settings['clinic_tagline'] ?? '') ?>">
      </div>
      <div class="col-12">
        <label class="form-label">Address</label>
        <textarea name="clinic_address" class="form-control" rows="2"><?= View::e($settings['clinic_address'] ?? '') ?></textarea>
      </div>
      <div class="col-md-4">
        <label class="form-label">Phone</label>
        <input type="text" name="clinic_phone" class="form-control" value="<?= View::e($settings['clinic_phone'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Email</label>
        <input type="email" name="clinic_email" class="form-control" value="<?= View::e($settings['clinic_email'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Current Offer Badge</label>
        <input type="text" name="clinic_offer" class="form-control" value="<?= View::e($settings['clinic_offer'] ?? '') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Logo</label>
        <?php if (!empty($settings['clinic_logo'])): ?><div class="mb-2"><img src="<?= View::e($settings['clinic_logo']) ?>" height="40"></div><?php endif; ?>
        <input type="file" name="clinic_logo_file" class="form-control" accept="image/*,.svg">
      </div>
    </div>
  </div>

  <div class="card p-4 mb-3">
    <h6 class="text-uppercase text-muted small mb-3">Social Links</h6>
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Facebook URL</label>
        <input type="url" name="social_facebook" class="form-control" value="<?= View::e($settings['social_facebook'] ?? '') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Instagram URL</label>
        <input type="url" name="social_instagram" class="form-control" value="<?= View::e($settings['social_instagram'] ?? '') ?>">
      </div>
    </div>
  </div>

  <div class="card p-4 mb-3">
    <h6 class="text-uppercase text-muted small mb-3">Homepage Content</h6>
    <div class="row g-3">
      <div class="col-12">
        <label class="form-label">Hero Title</label>
        <input type="text" name="homepage_hero_title" class="form-control" value="<?= View::e($settings['homepage_hero_title'] ?? '') ?>">
      </div>
      <div class="col-12">
        <label class="form-label">Hero Subtitle</label>
        <textarea name="homepage_hero_subtitle" class="form-control" rows="2"><?= View::e($settings['homepage_hero_subtitle'] ?? '') ?></textarea>
      </div>
      <div class="col-12">
        <label class="form-label">About Text</label>
        <textarea name="homepage_about_text" class="form-control" rows="3"><?= View::e($settings['homepage_about_text'] ?? '') ?></textarea>
      </div>
      <div class="col-md-6">
        <label class="form-label">Vision</label>
        <textarea name="clinic_vision" class="form-control" rows="2"><?= View::e($settings['clinic_vision'] ?? '') ?></textarea>
      </div>
      <div class="col-md-6">
        <label class="form-label">Mission</label>
        <textarea name="clinic_mission" class="form-control" rows="2"><?= View::e($settings['clinic_mission'] ?? '') ?></textarea>
      </div>
    </div>
  </div>

  <button type="submit" class="btn btn-primary">Save Settings</button>
</form>
