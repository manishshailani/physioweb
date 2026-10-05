<?php use App\Core\View; ?>
<h2 class="mb-4">My Profile</h2>

<div class="card p-4 mb-4">
  <h6 class="text-uppercase text-muted small mb-3">Patient ID: <?= View::e($patient['patient_code']) ?> (cannot be changed)</h6>
  <form method="post" action="/patient/profile" class="row g-3">
    <?= $csrf_field ?>
    <div class="col-md-6">
      <label class="form-label">First Name <span class="text-danger">*</span></label>
      <input type="text" name="first_name" class="form-control <?= isset($flash_errors['first_name']) ? 'is-invalid' : '' ?>" value="<?= View::e($old['first_name'] ?? $patient['first_name']) ?>" required>
      <?php if (isset($flash_errors['first_name'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['first_name'][0]) ?></div><?php endif; ?>
    </div>
    <div class="col-md-6">
      <label class="form-label">Last Name <span class="text-danger">*</span></label>
      <input type="text" name="last_name" class="form-control <?= isset($flash_errors['last_name']) ? 'is-invalid' : '' ?>" value="<?= View::e($old['last_name'] ?? $patient['last_name']) ?>" required>
      <?php if (isset($flash_errors['last_name'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['last_name'][0]) ?></div><?php endif; ?>
    </div>
    <div class="col-md-6">
      <label class="form-label">Mobile <span class="text-danger">*</span></label>
      <input type="text" name="mobile" class="form-control <?= isset($flash_errors['mobile']) ? 'is-invalid' : '' ?>" value="<?= View::e($old['mobile'] ?? $patient['mobile']) ?>" required>
      <?php if (isset($flash_errors['mobile'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['mobile'][0]) ?></div><?php endif; ?>
    </div>
    <div class="col-md-6">
      <label class="form-label">Alternate Mobile</label>
      <input type="text" name="alt_mobile" class="form-control" value="<?= View::e($old['alt_mobile'] ?? $patient['alt_mobile'] ?? '') ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control <?= isset($flash_errors['email']) ? 'is-invalid' : '' ?>" value="<?= View::e($old['email'] ?? $patient['email'] ?? '') ?>">
      <?php if (isset($flash_errors['email'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['email'][0]) ?></div><?php endif; ?>
    </div>
    <div class="col-md-6">
      <label class="form-label">Blood Group</label>
      <input type="text" name="blood_group" class="form-control" value="<?= View::e($old['blood_group'] ?? $patient['blood_group'] ?? '') ?>">
    </div>
    <div class="col-md-12">
      <label class="form-label">Address</label>
      <input type="text" name="address" class="form-control" value="<?= View::e($old['address'] ?? $patient['address'] ?? '') ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">City</label>
      <input type="text" name="city" class="form-control" value="<?= View::e($old['city'] ?? $patient['city'] ?? '') ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">State</label>
      <input type="text" name="state" class="form-control" value="<?= View::e($old['state'] ?? $patient['state'] ?? '') ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">Pincode</label>
      <input type="text" name="pincode" class="form-control" value="<?= View::e($old['pincode'] ?? $patient['pincode'] ?? '') ?>">
    </div>
    <div class="col-md-12">
      <label class="form-label">Allergies</label>
      <textarea name="allergies" class="form-control" rows="2"><?= View::e($old['allergies'] ?? $patient['allergies'] ?? '') ?></textarea>
    </div>
    <div class="col-md-12">
      <label class="form-label">Existing Conditions</label>
      <textarea name="existing_conditions" class="form-control" rows="2"><?= View::e($old['existing_conditions'] ?? $patient['existing_conditions'] ?? '') ?></textarea>
    </div>
    <div class="col-md-12">
      <label class="form-label">Medical History</label>
      <textarea name="medical_history" class="form-control" rows="2"><?= View::e($old['medical_history'] ?? $patient['medical_history'] ?? '') ?></textarea>
    </div>
    <div class="col-md-4">
      <label class="form-label">Emergency Contact Name</label>
      <input type="text" name="emergency_contact_name" class="form-control" value="<?= View::e($old['emergency_contact_name'] ?? $patient['emergency_contact_name'] ?? '') ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">Relationship</label>
      <input type="text" name="emergency_contact_relationship" class="form-control" value="<?= View::e($old['emergency_contact_relationship'] ?? $patient['emergency_contact_relationship'] ?? '') ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">Emergency Contact Mobile</label>
      <input type="text" name="emergency_contact_mobile" class="form-control" value="<?= View::e($old['emergency_contact_mobile'] ?? $patient['emergency_contact_mobile'] ?? '') ?>">
    </div>

    <div class="col-12"><hr></div>
    <div class="col-12"><h6 class="text-uppercase text-muted small">Change Password (optional)</h6></div>
    <div class="col-md-4">
      <label class="form-label">Current Password</label>
      <input type="password" name="current_password" class="form-control <?= isset($flash_errors['current_password']) ? 'is-invalid' : '' ?>" autocomplete="current-password">
      <?php if (isset($flash_errors['current_password'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['current_password'][0]) ?></div><?php endif; ?>
    </div>
    <div class="col-md-4">
      <label class="form-label">New Password</label>
      <input type="password" name="new_password" class="form-control <?= isset($flash_errors['new_password']) ? 'is-invalid' : '' ?>" autocomplete="new-password">
      <?php if (isset($flash_errors['new_password'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['new_password'][0]) ?></div><?php endif; ?>
    </div>
    <div class="col-md-4">
      <label class="form-label">Confirm New Password</label>
      <input type="password" name="new_password_confirmation" class="form-control <?= isset($flash_errors['new_password_confirmation']) ? 'is-invalid' : '' ?>" autocomplete="new-password">
      <?php if (isset($flash_errors['new_password_confirmation'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['new_password_confirmation'][0]) ?></div><?php endif; ?>
    </div>

    <div class="col-12">
      <button type="submit" class="btn btn-primary">Save Changes</button>
    </div>
  </form>
</div>
