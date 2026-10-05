<?php
use App\Core\View;
/** @var string $active one of patients|appointments|revenue|treatments */
/** @var string $from */
/** @var string $to */
$tabs = [
    'patients' => ['label' => 'Patients', 'icon' => 'fa-user-injured'],
    'appointments' => ['label' => 'Appointments', 'icon' => 'fa-calendar-check'],
    'revenue' => ['label' => 'Revenue', 'icon' => 'fa-indian-rupee-sign'],
    'treatments' => ['label' => 'Treatments', 'icon' => 'fa-notes-medical'],
];
?>
<ul class="nav nav-tabs mb-3">
  <?php foreach ($tabs as $key => $tab): ?>
    <li class="nav-item">
      <a class="nav-link <?= $key === $active ? 'active' : '' ?>" href="/admin/reports/<?= $key ?>?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>">
        <i class="fa-solid <?= $tab['icon'] ?> me-1"></i><?= $tab['label'] ?>
      </a>
    </li>
  <?php endforeach; ?>
</ul>

<div class="card p-3 mb-4">
  <form method="get" action="/admin/reports/<?= $active ?>" class="row g-2 align-items-end">
    <div class="col-md-3">
      <label class="form-label small text-muted mb-1">From</label>
      <input type="date" name="from" class="form-control" value="<?= View::e($from) ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label small text-muted mb-1">To</label>
      <input type="date" name="to" class="form-control" value="<?= View::e($to) ?>">
    </div>
    <div class="col-md-2">
      <button type="submit" class="btn btn-outline-primary w-100"><i class="fa-solid fa-filter me-1"></i>Apply</button>
    </div>
    <div class="col-md-4 d-flex gap-2 justify-content-md-end">
      <a class="btn btn-outline-success" href="/admin/reports/<?= $active ?>/export/xlsx?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>">
        <i class="fa-solid fa-file-excel me-1"></i>Excel
      </a>
      <a class="btn btn-outline-danger" href="/admin/reports/<?= $active ?>/export/pdf?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>">
        <i class="fa-solid fa-file-pdf me-1"></i>PDF
      </a>
    </div>
  </form>
</div>
