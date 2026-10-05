<?php use App\Core\View; ?>
<section class="py-5 bg-soft" style="min-height:70vh;">
  <div class="container" style="max-width:520px;">
    <div class="card shadow-sm">
      <div class="card-body p-4">
        <h4 class="mb-3 text-center">Create Patient Account</h4>
        <?php if (!empty($flash_error)): ?><div class="alert alert-danger"><?= View::e($flash_error) ?></div><?php endif; ?>
        <form method="post" action="/patient/register">
          <?= $csrf_field ?>
          <div class="row">
            <div class="col-6 mb-3">
              <label class="form-label">First Name</label>
              <input type="text" name="first_name" class="form-control" value="<?= View::e($old['first_name'] ?? '') ?>" required>
            </div>
            <div class="col-6 mb-3">
              <label class="form-label">Last Name</label>
              <input type="text" name="last_name" class="form-control" value="<?= View::e($old['last_name'] ?? '') ?>" required>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Gender</label>
            <select name="gender" class="form-select" required>
              <option value="">Select...</option>
              <option value="male">Male</option>
              <option value="female">Female</option>
              <option value="other">Other</option>
            </select>
            <?php if (!empty($flash_errors['gender'])): ?><div class="text-danger small"><?= View::e($flash_errors['gender'][0]) ?></div><?php endif; ?>
          </div>
          <div class="mb-3">
            <label class="form-label">Mobile</label>
            <input type="text" name="mobile" class="form-control" value="<?= View::e($old['mobile'] ?? '') ?>" required>
            <?php if (!empty($flash_errors['mobile'])): ?><div class="text-danger small"><?= View::e($flash_errors['mobile'][0]) ?></div><?php endif; ?>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?= View::e($old['email'] ?? '') ?>" required>
            <?php if (!empty($flash_errors['email'])): ?><div class="text-danger small"><?= View::e($flash_errors['email'][0]) ?></div><?php endif; ?>
          </div>
          <div class="row">
            <div class="col-6 mb-3">
              <label class="form-label">Password</label>
              <input type="password" name="password" class="form-control" required minlength="8">
              <?php if (!empty($flash_errors['password'])): ?><div class="text-danger small"><?= View::e($flash_errors['password'][0]) ?></div><?php endif; ?>
            </div>
            <div class="col-6 mb-3">
              <label class="form-label">Confirm Password</label>
              <input type="password" name="password_confirmation" class="form-control" required minlength="8">
            </div>
          </div>
          <button class="btn btn-primary w-100" type="submit">Create Account</button>
        </form>
        <p class="text-center mt-3 mb-0"><a href="/patient/login">Already have an account? Log in</a></p>
      </div>
    </div>
  </div>
</section>
