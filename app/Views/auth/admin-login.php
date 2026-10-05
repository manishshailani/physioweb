<?php use App\Core\View; ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Staff Login</title>
<link rel="icon" href="/assets/img/logo-icon.svg">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="/assets/css/site.css" rel="stylesheet">
</head>
<body class="bg-dark d-flex align-items-center" style="min-height:100vh;">
<div class="container" style="max-width:420px;">
  <div class="text-center mb-4">
    <img src="/assets/img/logo.svg" height="60" alt="Logo">
  </div>
  <div class="card shadow">
    <div class="card-body p-4">
      <h4 class="mb-3 text-center">Staff / Admin Login</h4>
      <?php if (!empty($flash_error)): ?><div class="alert alert-danger"><?= View::e($flash_error) ?></div><?php endif; ?>
      <form method="post" action="/admin/login">
        <?= $csrf_field ?>
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" value="<?= View::e($old['email'] ?? '') ?>" required autofocus>
          <?php if (!empty($flash_errors['email'])): ?><div class="text-danger small"><?= View::e($flash_errors['email'][0]) ?></div><?php endif; ?>
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <button class="btn btn-primary w-100" type="submit">Log In</button>
      </form>
    </div>
  </div>
  <p class="text-center text-secondary mt-3"><a href="/" class="text-secondary">&larr; Back to website</a></p>
</div>
</body>
</html>
