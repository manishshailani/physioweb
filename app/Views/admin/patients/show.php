<?php
use App\Core\View;
use App\Models\Patient;
use App\Models\Invoice;

$statusBadgeMap = [
    'scheduled' => 'secondary', 'confirmed' => 'primary', 'completed' => 'success',
    'cancelled' => 'danger', 'no_show' => 'warning',
    'active' => 'primary', 'discontinued' => 'secondary',
    'unpaid' => 'danger', 'partially_paid' => 'warning', 'paid' => 'success',
];
$badge = static fn (string $status): string => $statusBadgeMap[$status] ?? 'secondary';
?>
<div class="d-flex justify-content-between align-items-start mb-4">
  <div>
    <h2 class="mb-1"><?= View::e(Patient::fullName($patient)) ?>
      <span class="badge bg-light text-dark border"><?= View::e($patient['patient_code']) ?></span>
    </h2>
    <p class="text-muted mb-0">
      <?= View::e(ucfirst($patient['gender'])) ?>
      <?php $age = Patient::age($patient); ?>
      <?= $age !== null ? ' &middot; ' . $age . ' yrs' : '' ?>
      &middot; <i class="fa-solid fa-phone"></i> <?= View::e($patient['mobile']) ?>
      <?php if (!empty($patient['email'])): ?> &middot; <i class="fa-solid fa-envelope"></i> <?= View::e($patient['email']) ?><?php endif; ?>
    </p>
  </div>
  <div class="d-flex gap-2">
    <a href="/admin/appointments/create?patient_id=<?= (int) $patient['id'] ?>" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-calendar-plus me-1"></i>Book Appointment</a>
    <a href="/admin/patients/<?= (int) $patient['id'] ?>/edit" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-pen me-1"></i>Edit</a>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-6">
    <div class="card p-3 h-100">
      <h6 class="text-uppercase text-muted small mb-3">Contact &amp; Address</h6>
      <p class="mb-1"><strong>Mobile:</strong> <?= View::e($patient['mobile']) ?><?php if (!empty($patient['alt_mobile'])): ?> / <?= View::e($patient['alt_mobile']) ?><?php endif; ?></p>
      <p class="mb-1"><strong>Address:</strong> <?= View::e(trim(($patient['address'] ?? '') . ', ' . ($patient['city'] ?? '') . ', ' . ($patient['state'] ?? '') . ' ' . ($patient['pincode'] ?? ''), ', ')) ?></p>
      <p class="mb-0"><strong>Blood Group:</strong> <?= View::e($patient['blood_group'] ?? 'Unknown') ?></p>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card p-3 h-100">
      <h6 class="text-uppercase text-muted small mb-3">Medical Background</h6>
      <p class="mb-1"><strong>Allergies:</strong> <?= View::e($patient['allergies'] ?? 'None recorded') ?></p>
      <p class="mb-1"><strong>Existing Conditions:</strong> <?= View::e($patient['existing_conditions'] ?? 'None recorded') ?></p>
      <p class="mb-0"><strong>Emergency Contact:</strong>
        <?= $patient['emergency_contact_name'] ? View::e($patient['emergency_contact_name']) . ' (' . View::e($patient['emergency_contact_relationship'] ?? '') . ') ' . View::e($patient['emergency_contact_mobile'] ?? '') : 'Not provided' ?>
      </p>
    </div>
  </div>
</div>

<ul class="nav nav-tabs mb-3" role="tablist">
  <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-appointments" type="button">Appointments (<?= count($appointments) ?>)</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-plans" type="button">Treatment Plans (<?= count($treatmentPlans) ?>)</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-assessments" type="button">Assessments (<?= count($assessments) ?>)</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-exercises" type="button">Exercises (<?= count($exerciseAssignments) ?>)</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-billing" type="button">Billing (<?= count($invoices) ?>)</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-packages" type="button">Packages (<?= count($packages) ?>)</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-documents" type="button">Documents (<?= count($documents) ?>)</button></li>
</ul>

<div class="tab-content">

  <div class="tab-pane fade show active" id="tab-appointments">
    <div class="card">
      <div class="table-responsive">
        <table class="table mb-0">
          <thead><tr><th>#</th><th>Date</th><th>Time</th><th>Service</th><th>Therapist</th><th>Status</th></tr></thead>
          <tbody>
            <?php if (!$appointments): ?><tr><td colspan="6" class="text-center text-muted py-4">No appointments yet.</td></tr><?php endif; ?>
            <?php foreach ($appointments as $a): ?>
              <tr>
                <td><?= View::e($a['appointment_number']) ?></td>
                <td><?= View::dateFormat($a['scheduled_date']) ?></td>
                <td><?= View::e($a['scheduled_time']) ?></td>
                <td><?= View::e($a['service_name'] ?? '') ?></td>
                <td><?= View::e($a['therapist_name'] ?? '') ?></td>
                <td><span class="badge bg-<?= $badge($a['status']) ?>"><?= View::e($a['status']) ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="tab-pane fade" id="tab-plans">
    <div class="d-flex justify-content-end mb-2">
      <a href="/admin/patients/<?= (int) $patient['id'] ?>/treatment-plans" class="btn btn-sm btn-outline-primary">Manage Treatment Plans &amp; Sessions</a>
    </div>
    <div class="card">
      <div class="table-responsive">
        <table class="table mb-0">
          <thead><tr><th>Plan #</th><th>Diagnosis</th><th>Start</th><th>End</th><th>Status</th></tr></thead>
          <tbody>
            <?php if (!$treatmentPlans): ?><tr><td colspan="5" class="text-center text-muted py-4">No treatment plans yet.</td></tr><?php endif; ?>
            <?php foreach ($treatmentPlans as $p): ?>
              <tr>
                <td><?= View::e($p['plan_number']) ?></td>
                <td><?= View::e($p['diagnosis']) ?></td>
                <td><?= View::dateFormat($p['start_date']) ?></td>
                <td><?= View::dateFormat($p['end_date']) ?: '&mdash;' ?></td>
                <td><span class="badge bg-<?= $badge($p['status']) ?>"><?= View::e($p['status']) ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="tab-pane fade" id="tab-assessments">
    <div class="d-flex justify-content-end mb-2">
      <a href="/admin/patients/<?= (int) $patient['id'] ?>/assessments" class="btn btn-sm btn-outline-primary">Manage Assessments</a>
    </div>
    <div class="card">
      <div class="table-responsive">
        <table class="table mb-0">
          <thead><tr><th>Date</th><th>Diagnosis</th><th>Pain (0-10)</th><th>Mobility</th><th>Recovery</th><th>Notes</th></tr></thead>
          <tbody>
            <?php if (!$assessments): ?><tr><td colspan="6" class="text-center text-muted py-4">No assessments recorded yet.</td></tr><?php endif; ?>
            <?php foreach ($assessments as $a): ?>
              <tr>
                <td><?= View::dateFormat($a['assessment_date']) ?></td>
                <td><?= View::e($a['diagnosis'] ?? '') ?></td>
                <td><?= $a['pain_scale'] !== null ? View::e($a['pain_scale']) : '&mdash;' ?></td>
                <td><?= $a['mobility_score'] !== null ? View::e($a['mobility_score']) : '&mdash;' ?></td>
                <td><?= View::e($a['recovery_status'] ?? '&mdash;') ?></td>
                <td><?= View::e($a['clinical_notes'] ?? '') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="tab-pane fade" id="tab-exercises">
    <div class="card p-3 mb-3">
      <h6 class="text-uppercase text-muted small mb-3">Assign Exercise</h6>
      <form method="post" action="/admin/patients/<?= (int) $patient['id'] ?>/exercises" class="row g-2 align-items-end">
        <?= $csrf_field ?>
        <div class="col-md-3">
          <label class="form-label small">Exercise</label>
          <select name="exercise_id" class="form-select" required>
            <option value="">Select...</option>
            <?php foreach ($exerciseLibrary as $ex): ?>
              <option value="<?= (int) $ex['id'] ?>"><?= View::e($ex['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label small">Repetitions</label>
          <input type="text" name="repetitions" class="form-control" placeholder="e.g. 3x10">
        </div>
        <div class="col-md-2">
          <label class="form-label small">Frequency</label>
          <input type="text" name="frequency" class="form-control" placeholder="e.g. Daily">
        </div>
        <div class="col-md-2">
          <label class="form-label small">Start Date</label>
          <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="col-md-2">
          <label class="form-label small">End Date</label>
          <input type="date" name="end_date" class="form-control">
        </div>
        <div class="col-md-1">
          <button type="submit" class="btn btn-primary w-100">Assign</button>
        </div>
      </form>
    </div>
    <div class="card">
      <div class="table-responsive">
        <table class="table mb-0">
          <thead><tr><th>Exercise</th><th>Category</th><th>Repetitions</th><th>Frequency</th><th>Start</th><th>End</th><th></th></tr></thead>
          <tbody>
            <?php if (!$exerciseAssignments): ?><tr><td colspan="7" class="text-center text-muted py-4">No exercises assigned yet.</td></tr><?php endif; ?>
            <?php foreach ($exerciseAssignments as $e): ?>
              <tr>
                <td><?= View::e($e['exercise_name']) ?></td>
                <td><?= View::e($e['category'] ?? '') ?></td>
                <td><?= View::e($e['repetitions'] ?? '') ?></td>
                <td><?= View::e($e['frequency'] ?? '') ?></td>
                <td><?= View::dateFormat($e['start_date']) ?></td>
                <td><?= View::dateFormat($e['end_date']) ?: '&mdash;' ?></td>
                <td>
                  <form method="post" action="/admin/exercise-assignments/<?= (int) $e['id'] ?>/delete" onsubmit="return confirm('Remove this exercise assignment?');">
                    <?= $csrf_field ?>
                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="tab-pane fade" id="tab-billing">
    <div class="card">
      <div class="table-responsive">
        <table class="table mb-0">
          <thead><tr><th>Invoice #</th><th>Date</th><th>Total</th><th>Paid</th><th>Outstanding</th><th>Status</th><th></th></tr></thead>
          <tbody>
            <?php if (!$invoices): ?><tr><td colspan="7" class="text-center text-muted py-4">No invoices yet.</td></tr><?php endif; ?>
            <?php foreach ($invoices as $inv): ?>
              <tr>
                <td><?= View::e($inv['invoice_number']) ?></td>
                <td><?= View::dateFormat($inv['invoice_date']) ?></td>
                <td>&#8377;<?= View::money($inv['total_amount']) ?></td>
                <td>&#8377;<?= View::money($inv['amount_paid']) ?></td>
                <td>&#8377;<?= View::money(Invoice::outstandingBalance($inv)) ?></td>
                <td><span class="badge bg-<?= $badge($inv['status']) ?>"><?= View::e($inv['status']) ?></span></td>
                <td><a href="/admin/invoices/<?= (int) $inv['id'] ?>" class="btn btn-sm btn-outline-primary">View</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="tab-pane fade" id="tab-packages">
    <div class="card p-3 mb-3">
      <h6 class="text-uppercase text-muted small mb-3">Subscribe to a Package</h6>
      <form method="post" action="/admin/patients/<?= (int) $patient['id'] ?>/packages" class="row g-2 align-items-end">
        <?= $csrf_field ?>
        <div class="col-md-8">
          <select name="package_id" class="form-select" required>
            <option value="">Select a package...</option>
            <?php foreach ($availablePackages as $pkg): ?>
              <option value="<?= (int) $pkg['id'] ?>"><?= View::e($pkg['name']) ?> &mdash; <?= (int) $pkg['total_sessions'] ?> sessions &mdash; &#8377;<?= View::money(\App\Models\Package::discountedPrice($pkg)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <button type="submit" class="btn btn-primary w-100">Subscribe</button>
        </div>
      </form>
    </div>
    <div class="card">
      <div class="table-responsive">
        <table class="table mb-0">
          <thead><tr><th>Package</th><th>Purchased</th><th>Expiry</th><th>Sessions Used</th><th>Total Sessions</th><th>Status</th></tr></thead>
          <tbody>
            <?php if (!$packages): ?><tr><td colspan="6" class="text-center text-muted py-4">No packages subscribed yet.</td></tr><?php endif; ?>
            <?php foreach ($packages as $pkg): ?>
              <tr>
                <td><?= View::e($pkg['package_name']) ?></td>
                <td><?= View::dateFormat($pkg['purchased_date']) ?></td>
                <td><?= View::dateFormat($pkg['expiry_date']) ?></td>
                <td><?= (int) $pkg['sessions_used'] ?></td>
                <td><?= (int) $pkg['total_sessions'] ?></td>
                <td><span class="badge bg-<?= $pkg['status'] === 'active' ? 'success' : 'secondary' ?>"><?= View::e($pkg['status']) ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="tab-pane fade" id="tab-documents">
    <div class="card p-3 mb-3">
      <form method="post" action="/admin/patients/<?= (int) $patient['id'] ?>/documents" enctype="multipart/form-data" class="row g-2 align-items-end">
        <?= $csrf_field ?>
        <div class="col-md-4">
          <label class="form-label">Document Type</label>
          <select name="document_type" class="form-select">
            <?php foreach (['id_proof' => 'ID Proof', 'medical_report' => 'Medical Report', 'scan' => 'Scan', 'xray' => 'X-Ray', 'mri' => 'MRI', 'other' => 'Other'] as $value => $label): ?>
              <option value="<?= $value ?>"><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-5">
          <label class="form-label">File</label>
          <input type="file" name="document" class="form-control" required>
        </div>
        <div class="col-md-3">
          <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-upload me-1"></i>Upload</button>
        </div>
      </form>
    </div>
    <div class="card">
      <div class="table-responsive">
        <table class="table mb-0">
          <thead><tr><th>File</th><th>Type</th><th>Uploaded</th><th></th></tr></thead>
          <tbody>
            <?php if (!$documents): ?><tr><td colspan="4" class="text-center text-muted py-4">No documents uploaded yet.</td></tr><?php endif; ?>
            <?php foreach ($documents as $doc): ?>
              <tr>
                <td><?= View::e($doc['original_name']) ?></td>
                <td class="text-capitalize"><?= View::e(str_replace('_', ' ', $doc['document_type'])) ?></td>
                <td><?= View::dateFormat($doc['created_at']) ?></td>
                <td><a href="/admin/documents/<?= (int) $doc['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-download"></i></a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>
