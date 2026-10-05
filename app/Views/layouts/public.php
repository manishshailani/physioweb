<?php
/** @var array $settings */
/** @var string $page_title */
use App\Core\View;

$clinicName = $settings['clinic_name'] ?? 'PhysioWeb Clinic';
$fullTitle = ($page_title ?? '') !== '' ? $page_title . ' | ' . $clinicName : $clinicName;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= View::e($fullTitle) ?></title>
<meta name="description" content="<?= View::e($settings['homepage_hero_subtitle'] ?? $clinicName . ' - physiotherapy and rehabilitation clinic.') ?>">
<meta property="og:title" content="<?= View::e($fullTitle) ?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= View::e($clinicName) ?>">
<link rel="canonical" href="<?= View::e(($_SERVER['REQUEST_SCHEME'] ?? 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '/')) ?>">
<link rel="icon" type="image/svg+xml" href="/assets/img/logo-icon.svg">
<link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon-32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/assets/img/favicon-16.png">
<link rel="apple-touch-icon" sizes="180x180" href="/assets/img/apple-touch-icon.png">
<link rel="shortcut icon" href="/assets/img/favicon.ico">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<link href="/assets/css/site.css" rel="stylesheet">
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "MedicalClinic",
  "name": <?= json_encode($clinicName) ?>,
  "medicalSpecialty": "Physiotherapy",
  "address": <?= json_encode($settings['clinic_address'] ?? '') ?>,
  "telephone": <?= json_encode($settings['clinic_phone'] ?? '') ?>
}
</script>
</head>
<body>
<?php View::partial('partials.public-nav', ['settings' => $settings ?? []]); ?>

<?php if (!empty($flash_success)): ?>
<div class="container mt-3"><div class="alert alert-success"><?= View::e($flash_success) ?></div></div>
<?php endif; ?>
<?php if (!empty($flash_error)): ?>
<div class="container mt-3"><div class="alert alert-danger"><?= View::e($flash_error) ?></div></div>
<?php endif; ?>

<main>
<?= $content ?>
</main>

<?php View::partial('partials.public-footer', ['settings' => $settings ?? []]); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
