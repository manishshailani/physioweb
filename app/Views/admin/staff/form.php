<?php use App\Core\View; ?>
<h2 class="mb-4"><?= View::e($page_title) ?></h2>

<form method="post" action="/admin/staff/<?= (int) $staff['id'] ?>" enctype="multipart/form-data" class="card p-4">
  <?= $csrf_field ?>
  <div class="row g-3">
    <?php if (!empty($staff['photo'])): ?>
    <div class="col-12">
      <img src="<?= View::e($staff['photo']) ?>" style="width:80px;height:80px;object-fit:cover;border-radius:50%;">
    </div>
    <?php endif; ?>
    <div class="col-md-4">
      <label class="form-label">First Name <span class="text-danger">*</span></label>
      <input type="text" name="first_name" class="form-control <?= isset($flash_errors['first_name']) ? 'is-invalid' : '' ?>" value="<?= View::e($old['first_name'] ?? $staff['first_name']) ?>" required>
      <?php if (isset($flash_errors['first_name'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['first_name'][0]) ?></div><?php endif; ?>
    </div>
    <div class="col-md-4">
      <label class="form-label">Last Name</label>
      <input type="text" name="last_name" class="form-control" value="<?= View::e($old['last_name'] ?? $staff['last_name']) ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">Role <span class="text-danger">*</span></label>
      <select name="role" class="form-select" required>
        <?php foreach ($roles as $r): ?>
          <option value="<?= $r ?>" <?= ($old['role'] ?? $staff['role']) === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-6">
      <label class="form-label">Update Photo</label>
      <input type="file" name="photo" class="form-control" accept="image/*">
    </div>
    <div class="col-md-6">
      <label class="form-label">Qualification</label>
      <input type="text" name="qualification" class="form-control" value="<?= View::e($old['qualification'] ?? $staff['qualification'] ?? '') ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">Specialization</label>
      <input type="text" name="specialization" class="form-control" value="<?= View::e($old['specialization'] ?? $staff['specialization'] ?? '') ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">Experience (yrs)</label>
      <input type="number" step="0.5" name="experience_years" class="form-control" value="<?= View::e($old['experience_years'] ?? $staff['experience_years'] ?? '') ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Phone</label>
      <input type="text" name="phone" class="form-control" value="<?= View::e($old['phone'] ?? $staff['phone'] ?? '') ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" value="<?= View::e($old['email'] ?? $staff['email'] ?? '') ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">Joining Date</label>
      <input type="date" name="joining_date" class="form-control" value="<?= View::e($old['joining_date'] ?? $staff['joining_date'] ?? '') ?>">
    </div>
  </div>
  <div class="mt-3 d-flex gap-2">
    <button type="submit" class="btn btn-primary">Save Changes</button>
    <a href="/admin/staff" class="btn btn-outline-secondary">Cancel</a>
  </div>
</form>
