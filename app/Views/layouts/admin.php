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
<title><?= View::e($page_title ?? 'Admin') ?> | Clinic CMS</title>
<link rel="icon" href="/assets/img/logo-icon.svg">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<link href="/assets/css/site.css" rel="stylesheet">
<link href="/assets/css/admin.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
  <nav class="admin-sidebar text-white p-3" style="width:250px; position:sticky; top:0; height:100vh; overflow-y:auto;">
    <a href="/admin" class="d-block mb-4 text-center">
      <img src="/assets/img/logo-icon.svg" height="48" alt="Logo">
    </a>
    <ul class="nav nav-pills flex-column gap-1">
      <?php $navItem('/admin', 'fa-gauge', 'Dashboard'); ?>
      <?php $navItem('/admin/patients', 'fa-user-injured', 'Patients'); ?>
      <?php $navItem('/admin/appointments', 'fa-calendar-check', 'Appointments'); ?>
      <?php $navItem('/admin/exercises', 'fa-dumbbell', 'Exercise Library'); ?>
      <?php $navItem('/admin/services', 'fa-list-check', 'Services'); ?>
      <?php $navItem('/admin/invoices', 'fa-file-invoice-dollar', 'Invoices'); ?>
      <?php $navItem('/admin/packages', 'fa-box', 'Packages'); ?>
      <?php $navItem('/admin/therapists', 'fa-user-doctor', 'Therapists'); ?>
      <?php $navItem('/admin/staff', 'fa-id-badge', 'Staff'); ?>
      <?php $navItem('/admin/users', 'fa-user-shield', 'CMS Users'); ?>
      <li class="nav-item mt-3 border-top border-secondary pt-2"><span class="text-secondary small text-uppercase ps-2">Website Content</span></li>
      <?php $navItem('/admin/settings', 'fa-gear', 'Site Settings'); ?>
      <?php $navItem('/admin/blog', 'fa-newspaper', 'Blog'); ?>
      <?php $navItem('/admin/gallery', 'fa-images', 'Gallery'); ?>
      <?php $navItem('/admin/testimonials', 'fa-star', 'Testimonials'); ?>
      <?php $navItem('/admin/inquiries', 'fa-envelope', 'Inquiries'); ?>
      <li class="nav-item mt-3 border-top border-secondary pt-2"><span class="text-secondary small text-uppercase ps-2">Reports</span></li>
      <?php $navItem('/admin/reports/patients', 'fa-chart-column', 'Reports'); ?>
      <?php $navItem('/admin/audit-logs', 'fa-clipboard-list', 'Audit Log'); ?>
    </ul>
  </nav>
  <main class="flex-grow-1">
    <nav class="navbar navbar-light bg-white border-bottom px-3">
      <form class="d-flex flex-grow-1" method="get" action="/admin/search">
        <input type="search" name="q" class="form-control" style="max-width:320px;" placeholder="Search patients, invoices, appointments...">
      </form>
      <div class="d-flex align-items-center gap-3">
        <span class="text-muted small"><?= View::e($current_admin['name'] ?? '') ?> (<?= View::e($current_admin['role'] ?? '') ?>)</span>
        <form method="post" action="/admin/logout" class="m-0">
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
