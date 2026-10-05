<?php use App\Core\View; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h2 class="mb-0">Appointment Calendar</h2>
  <div class="d-flex gap-2 align-items-center">
    <select id="therapistFilter" class="form-select form-select-sm" style="width:auto;">
      <option value="">All Therapists</option>
      <?php foreach ($therapists as $t): ?>
        <option value="<?= (int) $t['id'] ?>"><?= View::e($t['first_name'] . ' ' . $t['last_name']) ?></option>
      <?php endforeach; ?>
    </select>
    <a href="/admin/appointments" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-list me-1"></i> List View</a>
    <a href="/admin/appointments/create" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus me-1"></i> New Appointment</a>
  </div>
</div>

<div class="card p-3 mb-3">
  <div class="d-flex flex-wrap gap-3 small text-muted">
    <span><span class="badge" style="background:#6c757d;">&nbsp;</span> Scheduled</span>
    <span><span class="badge" style="background:#0d6efd;">&nbsp;</span> Confirmed</span>
    <span><span class="badge" style="background:#198754;">&nbsp;</span> Completed</span>
    <span><span class="badge" style="background:#dc3545;">&nbsp;</span> Cancelled</span>
    <span><span class="badge" style="background:#fd7e14;">&nbsp;</span> No Show</span>
  </div>
</div>

<div class="card p-3">
  <div id="calendar"></div>
</div>

<!-- Appointment detail / status modal -->
<div class="modal fade" id="aptModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Appointment <span id="aptModalNumber"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-1"><strong>Patient:</strong> <span id="aptModalPatient"></span></p>
        <p class="mb-1"><strong>Therapist:</strong> <span id="aptModalTherapist"></span></p>
        <p class="mb-3"><strong>When:</strong> <span id="aptModalWhen"></span></p>
        <label class="form-label">Status</label>
        <select id="aptModalStatus" class="form-select">
          <?php foreach ($statuses as $s): ?>
            <option value="<?= $s ?>"><?= ucfirst(str_replace('_', ' ', $s)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="modal-footer">
        <a href="#" id="aptModalEditLink" class="btn btn-outline-secondary">Edit Details</a>
        <button type="button" class="btn btn-primary" id="aptModalSave">Save Status</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var calendarEl = document.getElementById('calendar');
  var therapistFilter = document.getElementById('therapistFilter');
  var csrfToken = <?= json_encode($csrf_token) ?>;
  var currentEventId = null;
  var modalEl = document.getElementById('aptModal');
  var modal = new bootstrap.Modal(modalEl);

  var calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay' },
    editable: true,
    eventStartEditable: true,
    height: 'auto',
    events: function (info, successCallback, failureCallback) {
      var url = '/admin/appointments/calendar/feed?start=' + encodeURIComponent(info.startStr) + '&end=' + encodeURIComponent(info.endStr);
      if (therapistFilter.value) { url += '&therapist_id=' + encodeURIComponent(therapistFilter.value); }
      fetch(url)
        .then(function (r) { return r.json(); })
        .then(successCallback)
        .catch(failureCallback);
    },
    eventDrop: function (info) { rescheduleEvent(info.event, info.revert); },
    eventResize: function (info) { rescheduleEvent(info.event, info.revert); },
    eventClick: function (info) {
      currentEventId = info.event.id;
      document.getElementById('aptModalNumber').textContent = info.event.extendedProps.appointment_number || '';
      document.getElementById('aptModalPatient').textContent = info.event.title || '';
      document.getElementById('aptModalTherapist').textContent = info.event.extendedProps.therapist || 'Unassigned';
      document.getElementById('aptModalWhen').textContent = info.event.start.toLocaleString();
      document.getElementById('aptModalStatus').value = info.event.extendedProps.status || 'scheduled';
      document.getElementById('aptModalEditLink').href = '/admin/appointments/' + currentEventId + '/edit';
      modal.show();
    }
  });
  calendar.render();

  therapistFilter.addEventListener('change', function () { calendar.refetchEvents(); });

  function rescheduleEvent(event, revert) {
    var date = event.start.toISOString().slice(0, 10);
    var time = event.start.toTimeString().slice(0, 8);
    fetch('/admin/appointments/' + event.id + '/reschedule', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
      body: '_csrf_token=' + encodeURIComponent(csrfToken) + '&scheduled_date=' + encodeURIComponent(date) + '&scheduled_time=' + encodeURIComponent(time)
    })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (!res.ok) { alert(res.error || 'Could not reschedule.'); revert(); }
      })
      .catch(function () { alert('Network error while rescheduling.'); revert(); });
  }

  document.getElementById('aptModalSave').addEventListener('click', function () {
    if (!currentEventId) { return; }
    fetch('/admin/appointments/' + currentEventId + '/status', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
      body: '_csrf_token=' + encodeURIComponent(csrfToken) + '&status=' + encodeURIComponent(document.getElementById('aptModalStatus').value)
    })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res.ok) { modal.hide(); calendar.refetchEvents(); }
        else { alert(res.error || 'Could not update status.'); }
      })
      .catch(function () { alert('Network error updating status.'); });
  });
});
</script>
