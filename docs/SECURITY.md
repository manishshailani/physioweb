# Security

This document describes every security control actually implemented in
this codebase, where to find it, and what still needs doing before a real
deployment. It intentionally does **not** claim more than the code does;
each control below is cited to the file that enforces it so it can be
independently verified, not taken on faith.

## Authentication

- **Password hashing**: bcrypt via PHP's `password_hash()` /
  `password_verify()` (`App\Core\Auth::hashPassword()`, used by both the
  admin and patient login/registration paths). Hashes are transparently
  upgraded with `password_needs_rehash()` on successful login if PHP's
  default bcrypt cost ever changes (see `Auth::attemptAdminLogin()` /
  `attemptPatientLogin()`).
- **Brute-force lockout**: `App\Models\LoginAttempt` records every
  attempt (success and failure) keyed by identifier + IP; `Auth::isLockedOut()`
  blocks further attempts once `LOGIN_MAX_ATTEMPTS` failures occur within
  `LOGIN_LOCKOUT_MINUTES` (both configurable via `.env`, defaults 5/15).
  Verified by `tests/smoke_test_models.php`'s "Auth: password hashing,
  login, lockout" section.
- **Session fixation**: `Session::regenerate()` is called on every
  successful login (admin and patient) before the session is trusted with
  the new privilege level.
- **Session cookies**: `HttpOnly` always, `Secure` when the request is
  detected as HTTPS (directly or behind a TLS-terminating proxy via
  `X-Forwarded-Proto`), `SameSite=Lax` (`App\Core\Session::start()`).
- **Idle timeout**: enforced server-side on every request from
  `SESSION_LIFETIME_MINUTES`, not just relying on the cookie's own
  expiry (`Session::start()`).
- **Role-based access control**: every admin controller action calls
  `requireAdmin()` (any logged-in staff) or `requireAdminRole('admin')`
  (admin role only &mdash; used for CMS User management, Site Settings,
  and the Audit Log screen) as its first line; every patient-portal
  controller action calls `requirePatient()`. See `App\Core\Controller`.

## Authorization / data isolation

- **Patient data ownership checks**: every patient-portal controller
  action that reads or mutates a specific row (an appointment, an invoice,
  a document) scopes its query to `$this->currentPatientId()` &mdash; the
  id from the authenticated session &mdash; and returns 404 if the row
  doesn't belong to that patient, rather than trusting an id from the URL.
  See `Patient\PatientAppointmentController::cancel()`,
  `Patient\PatientBillingController::invoicePdf()`,
  `Patient\DocumentController::download()`. Deliberately returns the same
  "not found" response whether the row doesn't exist or belongs to someone
  else, so a patient can't distinguish those two cases by probing ids.
  `tests/render_portal_test.php` verifies this against a second, real
  seeded patient (not just a missing id).
- **File access control**: patient documents are stored under
  `storage/uploads/`, outside the public web root, and are only ever
  reachable through `Admin\DocumentController` / `Patient\DocumentController`,
  both of which check authorization (and, for the patient variant,
  ownership) before streaming the file. See `App\Core\FileUpload`'s
  class-level doc comment for the full rationale.

## Injection

- **SQL injection**: every query in the codebase goes through
  `App\Core\Database`'s prepared-statement helpers
  (`query`/`fetchOne`/`fetchAll`/`fetchColumn`/`insert`/`update`/`delete`),
  which always bind parameters rather than interpolating values into SQL.
  The only string interpolation into SQL anywhere is of **hard-coded
  identifiers** that originate from PHP code, never from request input
  (e.g. `ORDER BY` column names built from a fixed internal allow-list,
  or `Database::concat()`'s column-name arguments) &mdash; see
  `Database`'s class-level doc comment.
- **XSS**: `App\Core\View::e()` is the single sanctioned escaping function
  (`htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`); every View template is
  expected to pass dynamic output through it. `View::raw()` exists only
  for content that was already sanitized at save time (not currently used
  for any user-supplied rich text in this codebase &mdash; there is no
  WYSIWYG field that accepts HTML).
- **Command injection**: this codebase never shells out to the OS with
  user-controlled input (no `exec`/`shell_exec`/`proc_open` anywhere in
  `app/`).
- **Content-Security-Policy / clickjacking / MIME-sniffing**:
  `App\Core\Response::securityHeaders()` is applied to every response
  (`public/index.php`) and sets `X-Content-Type-Options: nosniff`,
  `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`,
  and a `Content-Security-Policy` restricting script/style/font sources to
  `'self'` plus the specific CDNs this app actually loads from (jsDelivr,
  cdnjs, Google Fonts, code.jquery.com). **If you add a new CDN or inline
  script source, update the CSP in that one place.**

## CSRF

Every state-changing request (POST/PUT/DELETE) is expected to call
`Controller::verifyCsrf($request)` as its first line, which checks the
submitted token against `App\Core\Csrf`'s session-stored token and
rejects the request with HTTP 400 on mismatch, before touching the
database. Every form in this codebase includes `<?= $csrf_field ?>`.

## File uploads

`App\Core\FileUpload::store()` (used for patient documents, staff photos,
gallery images, blog images, and the site logo) applies, in order:
upload-error-code check, size limit, **extension allow-list** (never a
deny-list), and **real MIME-type verification via `finfo`** against the
file's actual bytes (never the client-supplied `Content-Type`, which is
attacker-controlled). The stored filename is always server-generated
(`random_bytes`-based), never derived from the original filename, both to
prevent path traversal and so an uploaded file can never be made to
execute as a script even if it ended up somewhere web-servable.

## Audit logging

Every sensitive create/update/delete/login/logout action calls
`App\Core\AuditLogger::log()`, which writes an append-only row to
`audit_logs` (actor, action, entity, a short description, IP address,
timestamp). The admin "Audit Logs" screen (`Admin\AuditLogController`,
`requireAdminRole('admin')`-restricted) is read-only by design &mdash;
there is no edit or delete action for an audit log row anywhere in this
codebase. Audit logging failures are caught and logged separately
(`AuditLogger::log()`'s own try/catch) so a logging failure can never
block or corrupt the primary action it's observing.

## Secrets

- No credential is hard-coded anywhere in `app/`, `config/`, or
  `database/` &mdash; everything sensitive comes from `.env` via
  `App\Core\Env` (see `config/config.php`). `.env` is git-ignored
  (`.gitignore`); only `.env.example` (placeholder values) is committed.
- `database/seed.mysql.sql`'s demo credentials are clearly documented as
  demo-only in its own header comment and in `docs/INSTALL.md` &mdash;
  **do not load that file into a production database**, or if you already
  have, change/remove those accounts immediately.

## What this document does NOT cover (production hardening checklist)

These are standard production concerns that are **infrastructure/ops
responsibilities**, not application code this project controls. Before
going live:

- [ ] TLS/HTTPS termination (the app detects and adapts to it, e.g.
      `Secure` cookies, but does not terminate TLS itself).
- [ ] MySQL user privileges scoped to only this database, not a superuser.
- [ ] Rate limiting at the reverse-proxy/WAF layer for public endpoints
      (the public booking form and contact form have no rate limiting
      beyond the login-lockout mechanism described above, which only
      applies to authentication endpoints).
- [ ] Regular `composer audit` / dependency updates for dompdf,
      PhpSpreadsheet, and PHPMailer (not something this document can
      verify once-and-for-all; dependency CVEs are discovered over time).
- [ ] Backups of the MySQL database and `storage/uploads/`.
- [ ] Log rotation for `storage/logs/` (nothing in this codebase rotates
      logs automatically).
- [ ] A real incident-response/responsible-disclosure contact if this is
      ever exposed to the public internet with real patient data &mdash;
      this is a healthcare-adjacent application (it stores medical
      history, allergies, assessments) and may be subject to data
      protection regulations in your jurisdiction (e.g. India's DPDP Act);
      that legal/compliance review is out of scope for this document and
      has not been performed here.
