<?php use App\Core\View; ?>
<h2 class="mb-4">Dashboard</h2>

<div class="row g-3 mb-4">
  <div class="col-md-3"><div class="card stat-card p-3"><div class="text-muted small">Total Patients</div><h3><?= $stats['total_patients'] ?></h3></div></div>
  <div class="col-md-3"><div class="card stat-card p-3"><div class="text-muted small">Today's Appointments</div><h3><?= $stats['today_appointments'] ?></h3></div></div>
  <div class="col-md-3"><div class="card stat-card p-3"><div class="text-muted small">Pending Appointments</div><h3><?= $stats['pending_appointments'] ?></h3></div></div>
  <div class="col-md-3"><div class="card stat-card p-3"><div class="text-muted small">Active Treatment Plans</div><h3><?= $stats['active_treatments'] ?></h3></div></div>
</div>
<div class="row g-3 mb-4">
  <div class="col-md-3"><div class="card stat-card p-3"><div class="text-muted small">Revenue Today</div><h3>&#8377;<?= View::money($stats['revenue_today']) ?></h3></div></div>
  <div class="col-md-3"><div class="card stat-card p-3"><div class="text-muted small">Revenue This Month</div><h3>&#8377;<?= View::money($stats['revenue_month']) ?></h3></div></div>
  <div class="col-md-3"><div class="card stat-card p-3"><div class="text-muted small">Outstanding Payments</div><h3 class="text-danger">&#8377;<?= View::money($stats['outstanding']) ?></h3></div></div>
  <div class="col-md-3"><div class="card stat-card p-3"><div class="text-muted small">New Inquiries</div><h3><?= $stats['new_inquiries'] ?></h3></div></div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-6"><div class="card p-3"><h6>Appointment Trend (30 days)</h6><canvas id="chartAppointments" height="180"></canvas></div></div>
  <div class="col-md-6"><div class="card p-3"><h6>Revenue Trend (30 days)</h6><canvas id="chartRevenue" height="180"></canvas></div></div>
</div>
<div class="row g-3 mb-4">
  <div class="col-md-6"><div class="card p-3"><h6>Treatment Category Distribution (90 days)</h6><canvas id="chartCategories" height="180"></canvas></div></div>
  <div class="col-md-6">
    <div class="card p-3">
      <h6>Recent Appointments</h6>
      <table class="table table-sm">
        <thead><tr><th>#</th><th>Patient</th><th>Date</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($recentAppointments as $a): ?>
          <tr><td><?= View::e($a['appointment_number']) ?></td><td><?= View::e($a['patient_name']) ?></td><td><?= View::dateFormat($a['scheduled_date']) ?></td>
          <td><span class="badge badge-status-<?= View::e($a['status']) ?>"><?= View::e($a['status']) ?></span></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
async function loadChart(url, canvasId, label, type) {
  const res = await fetch(url);
  const data = await res.json();
  new Chart(document.getElementById(canvasId), {
    type: type,
    data: { labels: data.labels, datasets: [{ label: label, data: data.values, borderColor: '#e8a33d', backgroundColor: 'rgba(232,163,61,0.35)', fill: type === 'line' }] },
    options: { responsive: true, plugins: { legend: { display: false } } }
  });
}
loadChart('/admin/dashboard/chart/appointments', 'chartAppointments', 'Appointments', 'line');
loadChart('/admin/dashboard/chart/revenue', 'chartRevenue', 'Revenue', 'line');
loadChart('/admin/dashboard/chart/treatment-categories', 'chartCategories', 'Appointments', 'bar');
</script>
