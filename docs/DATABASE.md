# Database Reference

The authoritative schema is **`database/schema.mysql.sql`** (MySQL 8+,
`InnoDB`, `utf8mb4`) &mdash; this document explains what each table is for,
how the tables relate, and *why* the schema makes the decisions it makes.
It deliberately does not reproduce every column (that would drift out of
sync with the real DDL); read the `.sql` file alongside this document.

A parallel **`database/schema.sqlite.sql`** exists solely for the
automated test suite (`tests/`) to run against an in-memory database with
no MySQL server required &mdash; see `tests/README.md`. Keep the two files
in sync by hand when the schema changes; `App\Core\Database`'s
`Database::concat()` and this project's avoidance of MySQL-only SQL
functions in application code (see that class's doc comment) is what lets
the same `App\Models\*` code run against either.

## Table groups

### Identity &amp; staff

- **`id_sequences`** &mdash; backs `App\Core\IdGenerator`'s human-readable
  sequential IDs (`PAT-2026-0001`, `APT-2026-0001`, `INV-2026-0001`,
  `PLAN-2026-0001`, `STF-2026-0001`). One row per `(prefix, year)`; the
  year component means sequences restart at `0001` each calendar year
  without colliding with the previous year's numbers (verified by
  `tests/smoke_test_models.php`'s "IdGenerator year rollover isolation").
- **`staff`** &mdash; every employee (physiotherapist, receptionist,
  assistant, admin). Never hard-deleted (see "Soft deletes" below).
- **`therapists`** &mdash; the subset of `staff` with a public-facing
  therapist profile (bio, slug, booking availability). One-to-one with
  `staff` via `staff_id`; a `staff` row with role `physiotherapist` doesn't
  automatically get a `therapists` row &mdash; that's a deliberate admin
  action (`Admin\TherapistController`) so a newly-hired physiotherapist
  isn't publicly bookable before their profile is actually ready.
- **`users`** &mdash; CMS/admin login accounts (`admin`, `receptionist`,
  `therapist_staff` roles). Distinct from `staff`: a `staff` row is an HR
  record, a `users` row is a login. `staff_id` links them but a staff
  member doesn't need a login (e.g. a part-time assistant who never
  touches the CMS).

### Patients &amp; clinical records

- **`patients`** &mdash; the patient record itself, including an optional
  `password_hash` for patient-portal self-service login.
- **`patient_documents`** &mdash; uploaded files (ID proof, medical
  reports, scans), stored under `storage/uploads/` outside the web root;
  see `docs/SECURITY.md`'s "File access control".
- **`appointments`** &mdash; bookable slots. `patient_id` is nullable
  specifically because the public booking form accepts guest bookings
  (`guest_name`/`guest_mobile`/`guest_email`) from people who aren't yet
  registered patients; `therapist_id`/`service_id` are nullable for the
  same reason (a guest might not specify either).
- **`treatment_plans`** / **`treatment_sessions`** &mdash; the EMR: a plan
  (diagnosis, status, date range) contains an ordered sequence of logged
  sessions (treatment given, pain/mobility scores before and after).
- **`assessments`** &mdash; periodic clinical assessments (pain scale,
  mobility score, recovery status) used for the progress chart on both
  the admin and patient-portal treatment-plan views; distinct from
  `treatment_sessions`' per-session scores (an assessment is a deliberate
  clinical checkpoint, not every single session).
- **`exercises`** (library) / **`exercise_assignments`** (prescriptions)
  &mdash; the exercise library is shared across all patients; an
  assignment links one patient to one library exercise with
  patient-specific repetitions/frequency/date range.

### Billing

- **`services`** &mdash; the billable/public service catalog (fee, GST
  rate, duration).
- **`packages`** / **`patient_packages`** &mdash; a package is a
  pre-purchased bundle of sessions at a discount; `patient_packages` is
  one patient's subscription to one package, tracking `sessions_used`
  against the package's `total_sessions` and an `expiry_date` computed
  from `validity_days` at purchase time (see
  `App\Models\PatientPackage::subscribe()`).
- **`invoices`** / **`invoice_items`** / **`payments`** &mdash; an invoice
  has one or more line items (each optionally linked to a `service_id`,
  but with its own frozen `description`/`unit_price`/`gst_rate` so a
  later price change to the service never alters a historical invoice)
  and zero or more payments. `invoices.status` /
  `invoices.amount_paid` are **derived, not independently editable**
  &mdash; they're recomputed from the sum of `payments` every time a
  payment is recorded or deleted
  (`App\Models\Invoice::recalculateStatus()`), so they can never drift out
  of sync with the payments actually on file.

### Website content (CMS)

- **`blog_categories`** / **`blogs`** &mdash; blog posts, optionally
  categorized (`blogs.category_id` is nullable; deleting a category sets
  it to `NULL` on its posts rather than deleting the posts &mdash; see
  `ON DELETE SET NULL` in the schema).
- **`gallery`** &mdash; image gallery for the public site.
- **`testimonials`** &mdash; patient testimonials with an
  approve/reject/pending workflow (`Admin\TestimonialController`); only
  `approved` ones are shown publicly.
- **`contact_inquiries`** &mdash; public contact-form submissions.
- **`settings`** &mdash; a single flexible key/value table for everything
  else editable from Admin &rarr; Site Settings (clinic name/address/phone,
  social links, homepage copy, logo path). See
  `Admin\SettingsController::KEYS` for the exact, closed list of keys this
  application actually reads/writes &mdash; adding a new setting means
  adding it to that list, not just inserting an arbitrary row.

### Security &amp; audit

- **`audit_logs`** &mdash; append-only trail of sensitive actions. See
  `docs/SECURITY.md`'s "Audit logging" section.
- **`login_attempts`** &mdash; every login attempt (success and failure),
  keyed by `(identifier, ip_address, scope, created_at)` for the
  brute-force lockout check (`App\Core\Auth::isLockedOut()`).

## Soft deletes

Patients, services, packages, staff, therapists, and exercises are
**never hard-deleted** by the application &mdash; "delete" in the admin UI
maps to `is_active = 0` (see each Model's own doc comment, e.g.
`App\Models\Patient`'s class-level comment). This is a deliberate decision
for two reasons:

1. **Foreign-key integrity**: these tables are referenced by historical
   records (invoices, appointments, treatment plans) that must remain
   intact for medico-legal and financial record-keeping even after the
   referenced patient/service/staff member is no longer active.
2. **Historical accuracy**: an invoice issued last year for a service that
   has since been discontinued, or a treatment plan assigned to a
   therapist who has since left, must still display correctly.

Every "deactivate" action in this codebase was verified against the
actual `ON DELETE` behavior declared in `database/schema.mysql.sql`
(`RESTRICT`, `CASCADE`, or `SET NULL`, per foreign key) before being
written, rather than assumed &mdash; check that constraint directly in the
schema file if you're ever unsure whether a given hard delete would even
be *possible* for a given table.

## Where a Model's query logic lives

`App\Core\Model` is a deliberately small base class (`find`, `all`,
`where`, `create`, `updateById`, `deleteById`, `count`, `paginate`) &mdash;
it is not a full ORM. Every join, aggregate, or report query lives as a
hand-written method directly on the relevant `App\Models\*` class (e.g.
`Invoice::paymentsBetween()`, `Appointment::listWithDetails()`,
`Patient::ageGroupDistribution()`), using `App\Core\Database`'s prepared-
statement helpers directly. There is no query builder or lazy-loading
relationship magic to reason about; every query a page runs is visible by
reading that page's Controller action and the Model methods it calls.
