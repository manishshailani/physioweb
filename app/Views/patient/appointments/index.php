<?php use App\Core\View; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h2 class="mb-0">My Appointments</h2>
  <a href="/patient/appointments/book" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i> Book New</a>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr><th>Appt #</th><th>Date</th><th>Time</th><th>Therapist</th><th>Service</th><th>Status</th><th class="text-end">Actions</th></tr>
      </thead>
      <tbody>
        <?php if (!$appointments): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">You have no appointments yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($appointments as $a): ?>
          <tr>
            <td><?= View::e($a['appointment_number']) ?></td>
            <td><?= View::dateFormat($a['scheduled_date']) ?></td>
            <td><?= View::e($a['scheduled_time']) ?></td>
            <td><?= View::e($a['therapist_name'] ?? 'To be assigned') ?></td>
            <td><?= View::e($a['service_name'] ?? 'General Consultation') ?></td>
            <td><span class="badge bg-light text-dark border"><?= View::e(ucfirst($a['status'])) ?></span></td>
            <td class="text-end">
              <?php if (in_array($a['status'], ['scheduled', 'confirmed'], true)): ?>
                <form method="post" action="/patient/appointments/<?= (int) $a['id'] ?>/cancel" onsubmit="return confirm('Cancel this appointment?');" class="d-inline">
                  <?= $csrf_field ?>
                  <button type="submit" class="btn btn-sm btn-outline-danger">Cancel</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
