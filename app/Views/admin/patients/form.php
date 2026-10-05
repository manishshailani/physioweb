<?php
use App\Core\View;

/** @var array|null $patient */
$isEdit = $patient !== null;
$action = $isEdit ? '/admin/patients/' . (int) $patient['id'] : '/admin/patients';

/** Resolve a field's display value: flashed old input first, then the existing patient record, then blank. */
$val = static function (string $field) use ($old, $patient): string {
    if (array_key_exists($field, $old)) {
        return (string) $old[$field];
    }
    if ($patient !== null && array_key_exists($field, $patient)) {
        return (string) ($patient[$field] ?? '');
    }
    return '';
};
$err = static function (string $field) use ($flash_errors): ?string {
    return $flash_errors[$field][0] ?? null;
};
?>
<h2 class="mb-4"><?= View::e($page_title) ?></h2>

<form method="post" action="<?= View::e($action) ?>" novalidate>
  <?= $csrf_field ?>

  <div class="card p-4 mb-3">
    <h6 class="text-uppercase text-muted small mb-3">Personal Details</h6>
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">First Name <span class="text-danger">*</span></label>
        <input type="text" name="first_name" class="form-control <?= $err('first_name') ? 'is-invalid' : '' ?>" value="<?= View::e($val('first_name')) ?>" maxlength="100" required>
        <?php if ($err('first_name')): ?><div class="invalid-feedback"><?= View::e($err('first_name')) ?></div><?php endif; ?>
      </div>
      <div class="col-md-6">
        <label class="form-label">Last Name <span class="text-danger">*</span></label>
        <input type="text" name="last_name" class="form-control <?= $err('last_name') ? 'is-invalid' : '' ?>" value="<?= View::e($val('last_name')) ?>" maxlength="100" required>
        <?php if ($err('last_name')): ?><div class="invalid-feedback"><?= View::e($err('last_name')) ?></div><?php endif; ?>
      </div>
      <div class="col-md-4">
        <label class="form-label">Gender <span class="text-danger">*</span></label>
        <select name="gender" class="form-select <?= $err('gender') ? 'is-invalid' : '' ?>" required>
          <option value="">Select</option>
          <?php foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $value => $label): ?>
            <option value="<?= $value ?>" <?= $val('gender') === $value ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
        <?php if ($err('gender')): ?><div class="invalid-feedback"><?= View::e($err('gender')) ?></div><?php endif; ?>
      </div>
      <div class="col-md-4">
        <label class="form-label">Date of Birth</label>
        <input type="date" name="date_of_birth" class="form-control <?= $err('date_of_birth') ? 'is-invalid' : '' ?>" value="<?= View::e($val('date_of_birth')) ?>">
        <?php if ($err('date_of_birth')): ?><div class="invalid-feedback"><?= View::e($err('date_of_birth')) ?></div><?php endif; ?>
      </div>
      <div class="col-md-4">
        <label class="form-label">Blood Group</label>
        <select name="blood_group" class="form-select">
          <option value="">Unknown</option>
          <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg): ?>
            <option value="<?= $bg ?>" <?= $val('blood_group') === $bg ? 'selected' : '' ?>><?= $bg ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>

  <div class="card p-4 mb-3">
    <h6 class="text-uppercase text-muted small mb-3">Contact Details</h6>
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Mobile <span class="text-danger">*</span></label>
        <input type="text" name="mobile" class="form-control <?= $err('mobile') ? 'is-invalid' : '' ?>" value="<?= View::e($val('mobile')) ?>" required>
        <?php if ($err('mobile')): ?><div class="invalid-feedback"><?= View::e($err('mobile')) ?></div><?php endif; ?>
      </div>
      <div class="col-md-4">
        <label class="form-label">Alternate Mobile</label>
        <input type="text" name="alt_mobile" class="form-control" value="<?= View::e($val('alt_mobile')) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control <?= $err('email') ? 'is-invalid' : '' ?>" value="<?= View::e($val('email')) ?>">
        <?php if ($err('email')): ?><div class="invalid-feedback"><?= View::e($err('email')) ?></div><?php endif; ?>
      </div>
      <div class="col-md-8">
        <label class="form-label">Address</label>
        <input type="text" name="address" class="form-control" value="<?= View::e($val('address')) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">City</label>
        <input type="text" name="city" class="form-control" value="<?= View::e($val('city')) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">State</label>
        <input type="text" name="state" class="form-control" value="<?= View::e($val('state')) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Country</label>
        <input type="text" name="country" class="form-control" value="<?= View::e($val('country') !== '' ? $val('country') : 'India') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Pincode</label>
        <input type="text" name="pincode" class="form-control" value="<?= View::e($val('pincode')) ?>" maxlength="10">
      </div>
    </div>
  </div>

  <div class="card p-4 mb-3">
    <h6 class="text-uppercase text-muted small mb-3">Medical Background</h6>
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Allergies</label>
        <textarea name="allergies" class="form-control" rows="2"><?= View::e($val('allergies')) ?></textarea>
      </div>
      <div class="col-md-6">
        <label class="form-label">Existing Conditions</label>
        <textarea name="existing_conditions" class="form-control" rows="2"><?= View::e($val('existing_conditions')) ?></textarea>
      </div>
      <div class="col-12">
        <label class="form-label">Medical History</label>
        <textarea name="medical_history" class="form-control" rows="3"><?= View::e($val('medical_history')) ?></textarea>
      </div>
    </div>
  </div>

  <div class="card p-4 mb-3">
    <h6 class="text-uppercase text-muted small mb-3">Emergency Contact</h6>
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Name</label>
        <input type="text" name="emergency_contact_name" class="form-control" value="<?= View::e($val('emergency_contact_name')) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Relationship</label>
        <input type="text" name="emergency_contact_relationship" class="form-control" value="<?= View::e($val('emergency_contact_relationship')) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Mobile</label>
        <input type="text" name="emergency_contact_mobile" class="form-control" value="<?= View::e($val('emergency_contact_mobile')) ?>">
      </div>
    </div>
  </div>

  <div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save Changes' : 'Register Patient' ?></button>
    <a href="<?= $isEdit ? '/admin/patients/' . (int) $patient['id'] : '/admin/patients' ?>" class="btn btn-outline-secondary">Cancel</a>
  </div>
</form>
