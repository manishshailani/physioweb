<?php
use App\Core\View;
use App\Models\Patient;

/** @var array|null $appointment */
$isEdit = $appointment !== null;
$action = $isEdit ? '/admin/appointments/' . (int) $appointment['id'] : '/admin/appointments';

$val = static function (string $field, mixed $default = '') use ($old, $appointment): mixed {
    if (array_key_exists($field, $old) && $old[$field] !== '') {
        return $old[$field];
    }
    if ($appointment !== null && array_key_exists($field, $appointment) && $appointment[$field] !== null) {
        return $appointment[$field];
    }
    return $default;
};
$err = static function (string $field) use ($flash_errors): ?string {
    return $flash_errors[$field][0] ?? null;
};
$selectedPatientId = $isEdit ? (int) ($appointment['patient_id'] ?? 0) : (int) $preselectedPatientId;
?>
<h2 class="mb-4"><?= View::e($page_title) ?></h2>

<form method="post" action="<?= View::e($action) ?>" novalidate>
  <?= $csrf_field ?>

  <div class="card p-4 mb-3">
    <h6 class="text-uppercase text-muted small mb-3">Who is this appointment for?</h6>
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Registered Patient</label>
        <input type="text" id="patientFilter" class="form-control mb-2" placeholder="Type to filter patients...">
        <select name="patient_id" id="patientSelect" class="form-select">
          <option value="">&mdash; Walk-in / Guest (no patient record) &mdash;</option>
          <?php foreach ($patients as $p): ?>
            <option value="<?= (int) $p['id'] ?>" data-name="<?= View::e(Patient::fullName($p)) ?>" <?= $selectedPatientId === (int) $p['id'] ? 'selected' : '' ?>>
              <?= View::e($p['patient_code'] . ' - ' . Patient::fullName($p) . ' (' . $p['mobile'] . ')') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6" id="guestFields" style="<?= $selectedPatientId > 0 ? 'display:none;' : '' ?>">
        <div class="row g-2">
          <div class="col-12">
            <label class="form-label">Guest Name <span class="text-danger">*</span></label>
            <input type="text" name="guest_name" class="form-control <?= $err('guest_name') ? 'is-invalid' : '' ?>" value="<?= View::e($val('guest_name')) ?>">
            <?php if ($err('guest_name')): ?><div class="invalid-feedback"><?= View::e($err('guest_name')) ?></div><?php endif; ?>
          </div>
          <div class="col-6">
            <label class="form-label">Guest Mobile <span class="text-danger">*</span></label>
            <input type="text" name="guest_mobile" class="form-control <?= $err('guest_mobile') ? 'is-invalid' : '' ?>" value="<?= View::e($val('guest_mobile')) ?>">
            <?php if ($err('guest_mobile')): ?><div class="invalid-feedback"><?= View::e($err('guest_mobile')) ?></div><?php endif; ?>
          </div>
          <div class="col-6">
            <label class="form-label">Guest Email</label>
            <input type="email" name="guest_email" class="form-control" value="<?= View::e($val('guest_email')) ?>">
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card p-4 mb-3">
    <h6 class="text-uppercase text-muted small mb-3">Schedule</h6>
    <div class="row g-3">
      <div class="col-md-3">
        <label class="form-label">Date <span class="text-danger">*</span></label>
        <input type="date" name="scheduled_date" class="form-control <?= $err('scheduled_date') ? 'is-invalid' : '' ?>" value="<?= View::e($val('scheduled_date', date('Y-m-d'))) ?>" required>
        <?php if ($err('scheduled_date')): ?><div class="invalid-feedback"><?= View::e($err('scheduled_date')) ?></div><?php endif; ?>
      </div>
      <div class="col-md-3">
        <label class="form-label">Time <span class="text-danger">*</span></label>
        <input type="time" name="scheduled_time" class="form-control <?= $err('scheduled_time') ? 'is-invalid' : '' ?>" value="<?= View::e(substr((string) $val('scheduled_time', '09:00:00'), 0, 5)) ?>" required>
        <?php if ($err('scheduled_time')): ?><div class="invalid-feedback"><?= View::e($err('scheduled_time')) ?></div><?php endif; ?>
      </div>
      <div class="col-md-3">
        <label class="form-label">Duration (minutes)</label>
        <input type="number" name="duration_minutes" id="durationInput" class="form-control" value="<?= (int) $val('duration_minutes', 45) ?>" min="5" step="5">
      </div>
      <div class="col-md-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
          <?php foreach ($statuses as $s): ?>
            <option value="<?= $s ?>" <?= $val('status', 'scheduled') === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $s)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Service</label>
        <select name="service_id" id="serviceSelect" class="form-select">
          <option value="">&mdash; None &mdash;</option>
          <?php foreach ($services as $s): ?>
            <option value="<?= (int) $s['id'] ?>" data-duration="<?= (int) $s['duration_minutes'] ?>" <?= (int) $val('service_id', 0) === (int) $s['id'] ? 'selected' : '' ?>>
              <?= View::e($s['name']) ?> (&#8377;<?= View::money($s['fee']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Therapist</label>
        <select name="therapist_id" class="form-select <?= $err('scheduled_time') ? 'is-invalid' : '' ?>">
          <option value="">&mdash; Unassigned &mdash;</option>
          <?php foreach ($therapists as $t): ?>
            <option value="<?= (int) $t['id'] ?>" <?= (int) $val('therapist_id', 0) === (int) $t['id'] ? 'selected' : '' ?>>
              <?= View::e($t['first_name'] . ' ' . $t['last_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control" rows="2"><?= View::e($val('notes')) ?></textarea>
      </div>
    </div>
  </div>

  <div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save Changes' : 'Book Appointment' ?></button>
    <a href="/admin/appointments" class="btn btn-outline-secondary">Cancel</a>
  </div>
</form>

<script>
(function () {
  var patientSelect = document.getElementById('patientSelect');
  var guestFields = document.getElementById('guestFields');
  var patientFilter = document.getElementById('patientFilter');
  var serviceSelect = document.getElementById('serviceSelect');
  var durationInput = document.getElementById('durationInput');

  function toggleGuestFields() {
    guestFields.style.display = patientSelect.value === '' ? '' : 'none';
  }
  patientSelect.addEventListener('change', toggleGuestFields);
  toggleGuestFields();

  patientFilter.addEventListener('input', function () {
    var term = patientFilter.value.toLowerCase();
    Array.prototype.forEach.call(patientSelect.options, function (opt) {
      if (opt.value === '') { return; }
      var name = (opt.dataset.name || '').toLowerCase();
      var text = opt.textContent.toLowerCase();
      opt.hidden = term !== '' && name.indexOf(term) === -1 && text.indexOf(term) === -1;
    });
  });

  serviceSelect.addEventListener('change', function () {
    var opt = serviceSelect.options[serviceSelect.selectedIndex];
    var duration = opt ? parseInt(opt.dataset.duration || '0', 10) : 0;
    if (duration > 0) { durationInput.value = duration; }
  });
})();
</script>
