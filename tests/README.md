# Automated Tests

This directory contains every automated check written for this project.
There is no PHPUnit (or any other test framework) installed in the
development sandbox this project was built in -- it has no network access
to Packagist -- so all of this is hand-rolled against a minimal assertion
harness (`assert_true()` / `assert_equals()` in `bootstrap_test.php`).
**If `composer install` succeeds in your environment, replacing this
harness with PHPUnit is a reasonable follow-up, but is not required for
these tests to keep working** -- they only depend on PHP itself plus the
`pdo_sqlite` extension.

Run everything with:

```bash
bash tests/run_all.sh
```

This does, in order: (1) `php -l` over every `.php` file in the project,
(2) `smoke_test_models.php`, (3) every `render_*_test.php` file. It stops
at the first failure.

## What this test suite proves, and how

Every test here runs against a **real, temporary SQLite database**
(`database/schema.sqlite.sql`, loaded fresh into `:memory:` for each test
file by `bootstrap_test.php`'s `physioweb_test_db()`), and exercises **real
Model and View code** -- not mocks. Concretely, for each `render_*_test.php`
file:

1. Rows are inserted through the actual `App\Models\*` classes (e.g.
   `Patient::register()`, `Appointment::book()`, `Invoice::createWithItems()`)
   -- never a hand-crafted INSERT that could bypass business logic the real
   code path enforces (ID generation, GST/discount math, status transitions,
   foreign keys).
2. Each admin/patient-portal view is rendered via `View::render()` /
   `View::renderWithLayout()` with exactly the data shape the real
   Controller action passes it (cross-checked against the Controller source
   at the time the test was written).
3. A `set_error_handler()` installed before any rendering treats **every
   PHP warning or notice as a hard test failure** -- an undefined-variable
   warning, a null passed where a string is expected, etc. all fail the run
   instead of being silently swallowed, which is specifically what catches
   a Controller/View data-shape mismatch (e.g. a View expecting a key the
   Controller forgot to pass).
4. Business-logic assertions (`assert_true()`) check the actual return
   values of Model methods -- GST totals, payment-status transitions,
   booking-conflict detection, report aggregates, ownership-isolation
   conditions, etc. -- against values computed independently in the test
   (e.g. `round(800 * 1.18 - 400, 2)`), not against a hand-typed "expected"
   number that could drift from the real formula.

| File | Covers |
|---|---|
| `smoke_test_models.php` | Core Model/business logic: ID generation, patient search, booking conflicts, invoice GST/discount math, payment status transitions, over-payment rejection, auth lockout, slug uniqueness, IdGenerator year rollover. |
| `render_patient_views_test.php` | Admin patient CRUD + the tabbed patient detail view. |
| `render_appointment_views_test.php` | Admin appointment CRUD, calendar feed, drag-and-drop reschedule conflict logic. |
| `render_emr_views_test.php` | Treatment plans/sessions, assessments + progress chart data, exercise library + assignment. |
| `render_billing_views_test.php` | Services, packages, invoices (create/list/show), payments, invoice PDF HTML template, PdfService's graceful-failure contract. |
| `render_cms_views_test.php` | Staff, therapists, CMS users, settings, blog (+categories), gallery, testimonials, inquiries. |
| `render_reports_test.php` | All 4 admin reports, audit log screen + its write/read path, global search, Excel/PDF export services' graceful-failure contract, `ReportController`'s private date-range/export-dataset logic (via Reflection). |
| `render_portal_test.php` | Patient portal: dashboard, appointments (book/cancel), treatment plan, exercises, billing, profile -- with a **second seeded patient** specifically to verify every ownership-isolation check (a patient can never read/act on another patient's data). |

## What this test suite does NOT prove

Be precise about the boundary here, because it matters for anyone deploying
this project:

- **No real MySQL server was ever used.** Every test runs against SQLite.
  `App\Core\Database` is written to keep all application SQL portable
  between the two drivers (see its class-level doc comment and
  `Database::concat()`), and the two schema files
  (`database/schema.mysql.sql`, `database/schema.sqlite.sql`) are kept in
  sync by hand, but MySQL-specific behavior (its exact `ENUM` enforcement,
  `ON UPDATE CURRENT_TIMESTAMP`, storage engine behavior, connection
  pooling under real concurrent load) has not been executed. **Run the
  full suite again against a real MySQL 8 instance before going to
  production** -- see `docs/INSTALL.md`.
- **No Composer dependency has actually been installed or executed.**
  This sandbox has no network access to Packagist, so `vendor/` does not
  exist here and never has. That means dompdf (`PdfService`), PhpSpreadsheet
  (`ExcelService`), and PHPMailer (`Mailer`) have all been **statically
  reviewed against their documented public APIs only** -- never run. Both
  `PdfService::renderHtmlToPdf()` and `ExcelService::buildWorkbook()` are
  written to throw a clear `RuntimeException` when their library is
  missing (verified by `render_billing_views_test.php` and
  `render_reports_test.php` respectively), and every Controller that calls
  them catches that exception and degrades gracefully (a flash message)
  rather than fatal-erroring -- but the actual PDF/Excel byte output has
  never been inspected. **Run `composer install` and manually download one
  invoice PDF, one Excel report, and send one real email before going to
  production.**
- **No WhatsApp/SMS provider was ever contacted.** `NotificationService`'s
  WhatsApp/SMS methods are statically reviewed against the configured
  provider's documented API shape only.
- **No HTTP request ever hit the Router.** Every test calls `View::render()`
  and Model methods directly; `app/Core/Router.php`, `public/index.php`,
  and the full `routes/web.php` wiring (every route resolves to the
  controller/method it claims to) have been read and cross-checked by
  hand, but never exercised by an actual request. `ReportController`,
  `Admin\DocumentController`, and `Patient\DocumentController` in
  particular have `header()`/`exit()`-ending code paths (file downloads)
  that are deliberately **not** called directly in these tests, since an
  `exit()` mid-test-run would terminate the test script itself -- their
  surrounding logic (ownership checks, HTML generation, graceful
  degradation) is tested instead; see the comment at the top of
  `render_reports_test.php` and `render_portal_test.php` for the specific
  reasoning. **Smoke-test every route by actually clicking through the app
  against a real web server before going to production.**
- **No file was ever actually uploaded through a browser.** `FileUpload`'s
  MIME-sniffing and size-limit logic has been read and is believed
  correct, but no test here drives an actual `multipart/form-data`
  request through it.
- **No concurrent/load testing of any kind.** Every test runs serially,
  single-process, single-request-equivalent.

In short: this suite gives high confidence in the **correctness of the PHP
business logic and the Controller/View data contract**, and essentially no
direct evidence about **infrastructure integration** (real database driver,
real third-party libraries, real HTTP, real file I/O, real external APIs).
Both matter before this goes live.
