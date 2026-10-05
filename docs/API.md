# Internal AJAX/JSON Endpoints

This project does **not** expose a public REST API for third-party
integration. Every endpoint below is called only by this application's own
admin-UI JavaScript (the FullCalendar view, Chart.js dashboards, and the
inquiry/appointment status dropdowns) and requires an authenticated admin
session exactly like any other `/admin/*` page &mdash; there is no
separate API token/key mechanism. They are documented here so a future
frontend change (or an integration that wants to reuse them) knows the
exact contract, not so an external system can be pointed at them as-is.

All endpoints:
- Require an active admin session (`Controller::requireAdmin()`); an
  unauthenticated request is redirected to `/admin/login`, not given a
  JSON error.
- POST endpoints require the same CSRF token as every other form in this
  app (`Controller::verifyCsrf()`); see `docs/SECURITY.md`.
- Return `Content-Type: application/json`.

## `GET /admin/dashboard/chart/{name}`

Feeds the three dashboard charts (`Admin\DashboardController::chartData()`).
`{name}` is one of:

| `{name}` | Response shape | Source |
|---|---|---|
| `appointments` | `{"labels": ["2026-09-01", ...], "values": [3, 5, ...]}` | `Appointment::trendLastNDays(30)` &mdash; appointment count per day, last 30 days. |
| `revenue` | `{"labels": ["2026-09-01", ...], "values": [1200.0, ...]}` | `Invoice::revenueTrend(30)` &mdash; payments received per day, last 30 days. |
| `treatment-categories` | `{"labels": ["Sports", ...], "values": [4, ...]}` | Appointment count per service category, last 90 days. |

Any other `{name}` returns HTTP 404.

## `GET /admin/appointments/calendar/feed`

Feeds the FullCalendar view on `/admin/appointments/calendar`
(`Admin\AppointmentController::calendarFeed()`). Query params: `start`,
`end` (date range, required by FullCalendar's own fetch), `status`,
`therapist_id` (optional filters, same semantics as the appointments list
page). Returns a JSON array of FullCalendar event objects:

```json
[
  {
    "id": 42,
    "title": "Ramesh Kulkarni - Sports Injury Rehabilitation",
    "start": "2026-10-04T10:00:00",
    "end": "2026-10-04T10:45:00",
    "color": "#0d6efd",
    "extendedProps": { "status": "confirmed", "therapist": "Dr. Meenakshi Kulkarni", "appointment_number": "APT-2026-0012" }
  }
]
```

`color` is derived from `status` via a fixed map in the controller
(scheduled=gray, confirmed=blue, completed=green, cancelled=red,
no_show=orange) &mdash; change that map, not the CSS, if you want
different calendar colors.

## `POST /admin/appointments/{id}/reschedule`

Called by FullCalendar's `eventDrop`/`eventResize` handlers when staff
drag-and-drop an appointment to a new slot
(`Admin\AppointmentController::reschedule()`). Body: `scheduled_date`
(`YYYY-MM-DD`), `scheduled_time` (`HH:MM` or `HH:MM:SS`).

Re-runs the same `Therapist::hasConflict()` double-booking check used
everywhere else appointments are booked/edited, excluding the appointment
being moved from its own conflict check.

| Response | Meaning |
|---|---|
| `200 {"ok": true}` | Rescheduled. |
| `404 {"ok": false, "error": "Appointment not found."}` | Bad id. |
| `422 {"ok": false, "error": "Invalid date or time."}` | Failed validation. |
| `409 {"ok": false, "error": "The therapist already has an overlapping appointment at that time."}` | Conflict detected &mdash; the calendar should revert the drag. |

## `POST /admin/appointments/{id}/status`

Called by the appointment status dropdown
(`Admin\AppointmentController::updateStatus()`). Body: `status` (one of
`scheduled`, `confirmed`, `completed`, `cancelled`, `no_show`).

| Response | Meaning |
|---|---|
| `200 {"ok": true, "status": "completed"}` | Updated. |
| `404 {"ok": false, "error": "Appointment not found."}` | Bad id. |
| `422 {"ok": false, "error": "Invalid status."}` | Not one of the allowed values. |

## `GET /admin/patients/{patientId}/progress`

Feeds the pain/mobility progress chart on the patient's Assessments tab
(`Admin\AssessmentController::progressData()`); only rendered when a
patient has 2+ assessments recorded. Response:

```json
{"labels": ["2026-08-01", "2026-09-01"], "pain": [7, 3], "mobility": [4, 8]}
```

`pain`/`mobility` entries are `null` where that particular assessment
didn't record a value, not `0` &mdash; a chart library should treat them
as a gap, not a zero score.

(The patient-portal equivalent, `/patient/treatment-plan`, does **not**
call this endpoint &mdash; its chart data is embedded directly in the
page as inline JSON at render time, since that page has no AJAX
dependency. See `app/Views/patient/treatment-plan.php`.)
