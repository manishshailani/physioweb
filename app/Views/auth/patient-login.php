<?php use App\Core\View; ?>
<section class="py-5 bg-soft" style="min-height:70vh;">
  <div class="container" style="max-width:420px;">
    <div class="card shadow-sm">
      <div class="card-body p-4">
        <h4 class="mb-3 text-center">Patient Login</h4>
        <?php if (!empty($flash_error)): ?><div class="alert alert-danger"><?= View::e($flash_error) ?></div><?php endif; ?>
        <form method="post" action="/patient/login">
          <?= $csrf_field ?>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?= View::e($old['email'] ?? '') ?>" required autofocus>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <button class="btn btn-primary w-100" type="submit">Log In</button>
        </form>
        <p class="text-center mt-3 mb-0"><a href="/patient/register">New patient? Create an account</a></p>
      </div>
    </div>
  </div>
</section>
