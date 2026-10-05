<?php use App\Core\View; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h2 class="mb-0">Appointments</h2>
  <div class="d-flex gap-2">
    <a href="/admin/appointments/calendar" class="btn btn-outline-primary"><i class="fa-solid fa-calendar-days me-1"></i> Calendar View</a>
    <a href="/admin/appointments/create" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i> New Appointment</a>
  </div>
</div>

<div class="card p-3 mb-3">
  <form method="get" action="/admin/appointments" class="row g-2">
    <div class="col-md-3">
      <label class="form-label small text-muted mb-1">Date</label>
      <input type="date" name="date" class="form-control" value="<?= View::e($filters['date']) ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label small text-muted mb-1">Status</label>
      <select name="status" class="form-select">
        <option value="">All</option>
        <?php foreach ($statuses as $s): ?>
          <option value="<?= $s ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $s)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label small text-muted mb-1">Therapist</label>
      <select name="therapist_id" class="form-select">
        <option value="">All</option>
        <?php foreach ($therapists as $t): ?>
          <option value="<?= (int) $t['id'] ?>" <?= (string) $t['id'] === $filters['therapist_id'] ? 'selected' : '' ?>><?= View::e($t['first_name'] . ' ' . $t['last_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3 d-flex align-items-end gap-2">
      <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
      <a href="/admin/appointments" class="btn btn-outline-secondary w-100">Clear</a>
    </div>
  </form>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr><th>#</th><th>Date</th><th>Time</th><th>Patient</th><th>Service</th><th>Therapist</th><th>Status</th><th class="text-end">Actions</th></tr>
      </thead>
      <tbody>
        <?php if (!$appointments): ?>
          <tr><td colspan="8" class="text-center text-muted py-4">No appointments found.</td></tr>
        <?php endif; ?>
        <?php foreach ($appointments as $a): ?>
          <tr>
            <td><?= View::e($a['appointment_number']) ?></td>
            <td><?= View::dateFormat($a['scheduled_date']) ?></td>
            <td><?= View::e($a['scheduled_time']) ?></td>
            <td><?= View::e($a['patient_name'] ?? '') ?></td>
            <td><?= View::e($a['service_name'] ?? '') ?></td>
            <td><?= View::e($a['therapist_name'] ?? '') ?></td>
            <td>
              <select class="form-select form-select-sm status-select" data-id="<?= (int) $a['id'] ?>" style="width:auto;">
                <?php foreach ($statuses as $s): ?>
                  <option value="<?= $s ?>" <?= $a['status'] === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $s)) ?></option>
                <?php endforeach; ?>
              </select>
            </td>
            <td class="text-end">
              <a href="/admin/appointments/<?= (int) $a['id'] ?>/edit" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="fa-solid fa-pen"></i></a>
              <form method="post" action="/admin/appointments/<?= (int) $a['id'] ?>/delete" class="d-inline" onsubmit="return confirm('Cancel this appointment?');">
                <?= $csrf_field ?>
                <button type="submit" class="btn btn-sm btn-outline-danger" title="Cancel"><i class="fa-solid fa-xmark"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($pagination['last_page'] > 1): ?>
<nav class="mt-3">
  <ul class="pagination justify-content-center">
    <?php for ($i = 1; $i <= $pagination['last_page']; $i++): ?>
      <li class="page-item <?= $i === $pagination['page'] ? 'active' : '' ?>">
        <a class="page-link" href="?page=<?= $i ?>&status=<?= urlencode($filters['status']) ?>&therapist_id=<?= urlencode($filters['therapist_id']) ?>&date=<?= urlencode($filters['date']) ?>"><?= $i ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>

<script>
document.querySelectorAll('.status-select').forEach(function (sel) {
  sel.addEventListener('change', function () {
    fetch('/admin/appointments/' + sel.dataset.id + '/status', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
      body: '_csrf_token=<?= urlencode($csrf_token) ?>&status=' + encodeURIComponent(sel.value)
    })
      .then(function (r) { return r.json(); })
      .then(function (res) { if (!res.ok) { alert(res.error || 'Could not update status.'); location.reload(); } })
      .catch(function () { alert('Network error updating status.'); location.reload(); });
  });
});
</script>
