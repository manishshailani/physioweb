# User Manual

Two audiences use this system: **clinic staff** (through the admin CMS)
and **patients** (through the patient portal and the public website).

## Part 1: Clinic Staff (Admin CMS)

Log in at `/admin/login`. What you can do depends on your role (set when
your account is created by an admin, under CMS Users):

| Role | Can access |
|---|---|
| `admin` | Everything, including CMS Users, Site Settings, and Audit Logs. |
| `receptionist` | Patients, appointments, billing, website content &mdash; everything except CMS Users, Site Settings, and Audit Logs. |
| `therapist_staff` | Same as receptionist (this system does not currently restrict a therapist's own account from, say, cancelling another therapist's appointment &mdash; role separation here is admin-vs-everyone-else, not a full per-feature permission matrix). |

### Dashboard

Shows today's appointment count, pending appointments, active treatment
plans, today's/this month's revenue, outstanding balance, and new
inquiries, plus three trend charts (appointments, revenue, treatment
categories). A global search box in the top bar (`/admin/search`) finds
patients, appointments, invoices, and staff by name/code/number.

### Patients

Admin &rarr; Patients. Register a new patient (gets a `PAT-YYYY-####`
code automatically), search/filter the list, and open a patient's detail
page for a tabbed view of their appointments, treatment plans,
assessments, exercises, billing history, packages, and uploaded documents.
"Delete" deactivates a patient (hides them from the default list) rather
than erasing their record &mdash; see `docs/DATABASE.md`'s "Soft deletes".

### Appointments

Admin &rarr; Appointments. List view with status/therapist/date filters,
or the Calendar view (drag-and-drop to reschedule, click to view/change
status). Booking checks for double-booking against the selected
therapist's existing schedule automatically.

### Treatment Plans, Assessments, Exercises (EMR)

From a patient's detail page: start a treatment plan (diagnosis, assigned
therapist), log sessions against it (treatment given, pain/mobility
scores before and after &mdash; once 2+ sessions are logged, a progress
chart appears), record periodic clinical assessments, and assign
exercises from the shared Exercise Library with patient-specific
repetitions/frequency.

### Billing

Admin &rarr; Services / Packages / Invoices. Create an invoice from one
or more line items (each optionally tied to a Service, with GST computed
automatically); record payments against it (over-payment is rejected);
download the invoice as a PDF or email it to the patient (requires
`composer install` to have fetched dompdf &mdash; see `docs/INSTALL.md`).
Subscribe a patient to a Package from their detail page's Packages tab.

### Staff &amp; Therapists

Admin &rarr; Staff: HR records for every employee. Admin &rarr;
Therapists: the subset of staff with a public booking profile (bio,
photo, slug) &mdash; a newly-added physiotherapist staff member does not
automatically appear on the public site until you create their therapist
profile here.

### Website Content

Admin &rarr; Site Settings, Blog, Gallery, Testimonials, Inquiries. Site
Settings controls the homepage copy, clinic contact info, social links,
and logo &mdash; the exact set of editable fields is fixed (see
`Admin\SettingsController::KEYS`). Testimonials submitted via other means
can be added directly (auto-approved); ones with a pending-approval
workflow would come from a future public-facing submission form (not
currently implemented &mdash; testimonials today are staff-entered only).

### Reports

Admin &rarr; Reports: four report types (Patients, Appointments, Revenue,
Treatments), each filterable by date range, with an Excel or PDF export
button (requires `composer install` to have fetched phpoffice/phpspreadsheet
and dompdf respectively).

### Audit Logs (admin role only)

Admin &rarr; Audit Log: a read-only, filterable trail of who did what and
when &mdash; every patient/invoice/appointment/user create, update,
delete, login, and logout is recorded here automatically. There is no way
to edit or delete an entry from this screen by design.

### CMS Users &amp; Settings (admin role only)

Admin &rarr; CMS Users: create/deactivate staff login accounts and assign
roles. You cannot deactivate your own account (a deliberate safeguard
against accidentally locking yourself out).

## Part 2: Patients

### Public website

Browse services, the team, the gallery, blog, and testimonials, and book
an appointment at `/book-appointment` without creating an account (a
"guest" booking) &mdash; the clinic will follow up to confirm.

### Patient portal

Register at `/patient/register` (or ask the clinic to set up your portal
login) and log in at `/patient/login`. From there you can:

- **Dashboard**: see your upcoming appointments, active exercises, and
  outstanding balance at a glance.
- **Appointments**: book a new appointment (optionally choosing a service
  and preferred therapist), view your appointment history, and cancel a
  scheduled/confirmed appointment yourself.
- **Treatment Plan**: view your active treatment plan, its logged
  sessions (with a pain/mobility progress chart once 2+ sessions exist),
  clinical assessments, and history of past plans.
- **My Exercises**: your currently-assigned home exercises (instructions,
  repetitions, frequency, and a video link where provided) and past
  assignments.
- **Billing &amp; Invoices**: your invoice history, outstanding balance,
  package subscriptions, and a PDF download for any of your own invoices.
- **My Profile**: update your own name, contact/address details, and
  medical-history fields (allergies, existing conditions, history), and
  change your password. Your Patient ID cannot be changed, and clinical
  records (treatment plans, assessments, session notes) are staff-entered
  only &mdash; contact the clinic to correct those.

You will only ever see your own data in the portal &mdash; see
`docs/SECURITY.md`'s "Authorization / data isolation" for how that's
enforced.
