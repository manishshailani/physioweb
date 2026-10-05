<?php
use App\Core\View;
use App\Models\Patient;
?>
<div class="d-flex justify-content-between align-items-start mb-4">
  <div>
    <h2 class="mb-1">Assessments</h2>
    <p class="text-muted mb-0"><?= View::e(Patient::fullName($patient)) ?> &middot; <?= View::e($patient['patient_code']) ?></p>
  </div>
  <a href="/admin/patients/<?= (int) $patient['id'] ?>" class="btn btn-outline-secondary btn-sm">&larr; Back to Patient</a>
</div>

<div class="card p-4 mb-4">
  <h6 class="text-uppercase text-muted small mb-3">New Assessment</h6>
  <form method="post" action="/admin/patients/<?= (int) $patient['id'] ?>/assessments" class="row g-3">
    <?= $csrf_field ?>
    <div class="col-md-3">
      <label class="form-label">Date <span class="text-danger">*</span></label>
      <input type="date" name="assessment_date" class="form-control <?= isset($flash_errors['assessment_date']) ? 'is-invalid' : '' ?>" value="<?= View::e($old['assessment_date'] ?? date('Y-m-d')) ?>" required>
      <?php if (isset($flash_errors['assessment_date'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['assessment_date'][0]) ?></div><?php endif; ?>
    </div>
    <div class="col-md-4">
      <label class="form-label">Therapist</label>
      <select name="therapist_id" class="form-select">
        <option value="">&mdash; Unassigned &mdash;</option>
        <?php foreach ($therapists as $t): ?>
          <option value="<?= (int) $t['id'] ?>"><?= View::e($t['first_name'] . ' ' . $t['last_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-5">
      <label class="form-label">Diagnosis</label>
      <input type="text" name="diagnosis" class="form-control" value="<?= View::e($old['diagnosis'] ?? '') ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Symptoms</label>
      <textarea name="symptoms" class="form-control" rows="2"><?= View::e($old['symptoms'] ?? '') ?></textarea>
    </div>
    <div class="col-md-6">
      <label class="form-label">Clinical Notes</label>
      <textarea name="clinical_notes" class="form-control" rows="2"><?= View::e($old['clinical_notes'] ?? '') ?></textarea>
    </div>
    <div class="col-md-3">
      <label class="form-label">Pain Scale (0-10)</label>
      <input type="number" name="pain_scale" class="form-control <?= isset($flash_errors['pain_scale']) ? 'is-invalid' : '' ?>" min="0" max="10" value="<?= View::e($old['pain_scale'] ?? '') ?>">
      <?php if (isset($flash_errors['pain_scale'])): ?><div class="invalid-feedback"><?= View::e($flash_errors['pain_scale'][0]) ?></div><?php endif; ?>
    </div>
    <div class="col-md-3">
      <label class="form-label">Mobility Score (0-10)</label>
      <input type="number" name="mobility_score" class="form-control" min="0" max="10" value="<?= View::e($old['mobility_score'] ?? '') ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Recovery Status</label>
      <select name="recovery_status" class="form-select">
        <option value="">&mdash; Not set &mdash;</option>
        <?php foreach ($recoveryStatuses as $rs): ?>
          <option value="<?= $rs ?>" <?= ($old['recovery_status'] ?? '') === $rs ? 'selected' : '' ?>><?= ucfirst($rs) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-12">
      <button type="submit" class="btn btn-primary">Save Assessment</button>
    </div>
  </form>
</div>

<?php if (count($assessments) >= 2): ?>
<div class="card p-3 mb-4">
  <h6 class="text-uppercase text-muted small mb-3">Pain &amp; Mobility Progress</h6>
  <canvas id="progressChart" height="90"></canvas>
</div>
<?php endif; ?>

<div class="card">
  <div class="table-responsive">
    <table class="table mb-0">
      <thead><tr><th>Date</th><th>Diagnosis</th><th>Pain</th><th>Mobility</th><th>Recovery</th><th>Notes</th><th></th></tr></thead>
      <tbody>
        <?php if (!$assessments): ?><tr><td colspan="7" class="text-center text-muted py-4">No assessments recorded yet.</td></tr><?php endif; ?>
        <?php foreach ($assessments as $a): ?>
          <tr>
            <td><?= View::dateFormat($a['assessment_date']) ?></td>
            <td><?= View::e($a['diagnosis'] ?? '') ?></td>
            <td><?= $a['pain_scale'] ?? '&mdash;' ?></td>
            <td><?= $a['mobility_score'] ?? '&mdash;' ?></td>
            <td><?= $a['recovery_status'] ? ucfirst($a['recovery_status']) : '&mdash;' ?></td>
            <td><?= View::e($a['clinical_notes'] ?? '') ?></td>
            <td>
              <form method="post" action="/admin/assessments/<?= (int) $a['id'] ?>/delete" onsubmit="return confirm('Delete this assessment?');">
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

<?php if (count($assessments) >= 2): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
fetch('/admin/patients/<?= (int) $patient['id'] ?>/progress')
  .then(function (r) { return r.json(); })
  .then(function (data) {
    new Chart(document.getElementById('progressChart'), {
      type: 'line',
      data: {
        labels: data.labels,
        datasets: [
          { label: 'Pain Scale', data: data.pain, borderColor: '#dc3545', backgroundColor: 'rgba(220,53,69,0.15)' },
          { label: 'Mobility Score', data: data.mobility, borderColor: '#198754', backgroundColor: 'rgba(25,135,84,0.15)' }
        ]
      },
      options: { responsive: true, scales: { y: { min: 0, max: 10 } } }
    });
  });
</script>
<?php endif; ?>
