<?php
use App\Core\View;
$currentPath = $_SERVER['REQUEST_URI'] ?? '';
$navItem = static function (string $href, string $icon, string $label) use ($currentPath) {
    $active = str_starts_with($currentPath, $href) ? 'active' : '';
    echo '<li class="nav-item"><a class="nav-link ' . $active . '" href="' . View::e($href) . '">'
        . '<i class="fa-solid ' . $icon . ' me-2"></i>' . View::e($label) . '</a></li>';
};
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= View::e($page_title ?? 'My Account') ?> | Patient Portal</title>
<link rel="icon" href="/assets/img/logo-icon.svg">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<link href="/assets/css/site.css" rel="stylesheet">
<link href="/assets/css/admin.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
  <nav class="admin-sidebar text-white p-3" style="width:250px; position:sticky; top:0; height:100vh; overflow-y:auto;">
    <a href="/patient" class="d-block mb-4 text-center">
      <img src="/assets/img/logo-icon.svg" height="48" alt="Logo">
    </a>
    <ul class="nav nav-pills flex-column gap-1">
      <?php $navItem('/patient', 'fa-gauge', 'Dashboard'); ?>
      <?php $navItem('/patient/appointments', 'fa-calendar-check', 'Appointments'); ?>
      <?php $navItem('/patient/treatment-plan', 'fa-notes-medical', 'Treatment Plan'); ?>
      <?php $navItem('/patient/exercises', 'fa-dumbbell', 'My Exercises'); ?>
      <?php $navItem('/patient/billing', 'fa-file-invoice-dollar', 'Billing & Invoices'); ?>
      <?php $navItem('/patient/profile', 'fa-user-gear', 'My Profile'); ?>
    </ul>
  </nav>
  <main class="flex-grow-1">
    <nav class="navbar navbar-light bg-white border-bottom px-3 justify-content-end">
      <div class="d-flex align-items-center gap-3">
        <span class="text-muted small">
          <?= View::e(trim(($current_patient['first_name'] ?? '') . ' ' . ($current_patient['last_name'] ?? ''))) ?>
          <?php if (!empty($current_patient['patient_code'])): ?>(<?= View::e($current_patient['patient_code']) ?>)<?php endif; ?>
        </span>
        <form method="post" action="/patient/logout" class="m-0">
          <?= $csrf_field ?>
          <button class="btn btn-sm btn-outline-secondary" type="submit">Logout</button>
        </form>
      </div>
    </nav>
    <div class="p-4">
      <?php if (!empty($flash_success)): ?><div class="alert alert-success"><?= View::e($flash_success) ?></div><?php endif; ?>
      <?php if (!empty($flash_error)): ?><div class="alert alert-danger"><?= View::e($flash_error) ?></div><?php endif; ?>
      <?= $content ?>
    </div>
  </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
