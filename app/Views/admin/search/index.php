<?php use App\Core\View; use App\Models\Patient; ?>
<h2 class="mb-1">Search Results</h2>
<p class="text-muted mb-4">
  <?php if ($term === ''): ?>
    Enter a search term above to find patients, appointments, invoices, or staff.
  <?php elseif ($tooShort): ?>
    Please enter at least 2 characters to search.
  <?php else: ?>
    <?= (int) $totalResults ?> result(s) for "<?= View::e($term) ?>"
  <?php endif; ?>
</p>

<?php if ($results['patients']): ?>
<div class="card mb-4">
  <div class="card-header fw-semibold"><i class="fa-solid fa-user-injured me-2"></i>Patients</div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr><th>Patient ID</th><th>Name</th><th>Mobile</th><th>Email</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($results['patients'] as $p): ?>
          <tr>
            <td><?= View::e($p['patient_code']) ?></td>
            <td><?= View::e(Patient::fullName($p)) ?></td>
            <td><?= View::e($p['mobile']) ?></td>
            <td><?= View::e($p['email'] ?? '') ?></td>
            <td class="text-end"><a href="/admin/patients/<?= (int) $p['id'] ?>" class="btn btn-sm btn-outline-primary">View</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php if ($results['appointments']): ?>
<div class="card mb-4">
  <div class="card-header fw-semibold"><i class="fa-solid fa-calendar-check me-2"></i>Appointments</div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr><th>Appt #</th><th>Patient</th><th>Date</th><th>Time</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($results['appointments'] as $a): ?>
          <tr>
            <td><?= View::e($a['appointment_number']) ?></td>
            <td><?= View::e($a['patient_name'] ?? '') ?></td>
            <td><?= View::dateFormat($a['scheduled_date']) ?></td>
            <td><?= View::e($a['scheduled_time']) ?></td>
            <td><span class="badge bg-light text-dark border"><?= View::e(ucfirst($a['status'])) ?></span></td>
            <td class="text-end"><a href="/admin/appointments/<?= (int) $a['id'] ?>/edit" class="btn btn-sm btn-outline-primary">View</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php if ($results['invoices']): ?>
<div class="card mb-4">
  <div class="card-header fw-semibold"><i class="fa-solid fa-file-invoice-dollar me-2"></i>Invoices</div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr><th>Invoice #</th><th>Patient</th><th>Date</th><th>Total</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($results['invoices'] as $inv): ?>
          <tr>
            <td><?= View::e($inv['invoice_number']) ?></td>
            <td><?= View::e($inv['patient_name'] ?? '') ?></td>
            <td><?= View::dateFormat($inv['invoice_date']) ?></td>
            <td>&#8377;<?= number_format((float) $inv['total_amount'], 2) ?></td>
            <td><span class="badge bg-light text-dark border"><?= View::e(ucfirst(str_replace('_', ' ', $inv['status']))) ?></span></td>
            <td class="text-end"><a href="/admin/invoices/<?= (int) $inv['id'] ?>" class="btn btn-sm btn-outline-primary">View</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php if ($results['staff']): ?>
<div class="card mb-4">
  <div class="card-header fw-semibold"><i class="fa-solid fa-id-badge me-2"></i>Staff</div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead><tr><th>Staff ID</th><th>Name</th><th>Role</th><th>Phone</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($results['staff'] as $s): ?>
          <tr>
            <td><?= View::e($s['staff_code']) ?></td>
            <td><?= View::e(trim($s['first_name'] . ' ' . $s['last_name'])) ?></td>
            <td><?= View::e(ucfirst($s['role'])) ?></td>
            <td><?= View::e($s['phone'] ?? '') ?></td>
            <td class="text-end"><a href="/admin/staff/<?= (int) $s['id'] ?>/edit" class="btn btn-sm btn-outline-primary">View</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php if ($term !== '' && !$tooShort && $totalResults === 0): ?>
  <div class="alert alert-light border text-center text-muted">No matches found for "<?= View::e($term) ?>".</div>
<?php endif; ?>
