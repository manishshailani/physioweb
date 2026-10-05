# PhysioWeb &mdash; Cortex &amp; Kinetic Physiotherapy and Rehabilitation Clinic

A complete clinic management system for a physiotherapy practice: a public
marketing website with online appointment booking, a staff CMS/admin
backend (patients, scheduling, EMR, billing, reports, audit log), and a
self-service patient portal.

Built for **Cortex &amp; Kinetic Physiotherapy and Rehabilitation Clinic**
(Belgaum), run by Dr. Meenakshi (BPT, MPT Neuro).

## Stack

- PHP 8.3+, hand-rolled MVC (no framework dependency) &mdash; see
  `app/Core/` for the Router, Controller, Model, Database, Auth, Session,
  View, and Validator primitives everything else is built on.
- MySQL 8+ (production) via PDO with prepared statements everywhere.
- Bootstrap 5 + vanilla JS/jQuery-free AJAX (FullCalendar for the
  appointment calendar, Chart.js for dashboard/progress charts).
- Composer for dependencies: dompdf (PDF), PhpOffice/PhpSpreadsheet
  (Excel), PHPMailer (email).

## Documentation

| Document | Covers |
|---|---|
| [`docs/INSTALL.md`](docs/INSTALL.md) | Server requirements, step-by-step setup, environment configuration, first deploy checklist. |
| [`docs/SECURITY.md`](docs/SECURITY.md) | Every security control implemented, why, and the production-hardening checklist. |
| [`docs/DATABASE.md`](docs/DATABASE.md) | Schema reference: every table, its relationships, and the key design decisions (soft deletes, ID generation, FK cascade behavior). |
| [`docs/API.md`](docs/API.md) | The internal AJAX/JSON endpoints the admin UI calls (calendar feed, chart data, status updates, drag-and-drop reschedule). This is not a public API. |
| [`docs/USER_MANUAL.md`](docs/USER_MANUAL.md) | How to use the system: staff/admin workflows and the patient portal. |
| [`tests/README.md`](tests/README.md) | What the automated test suite proves, exactly how, and &mdash; just as importantly &mdash; what it does **not** prove (no real MySQL/dompdf/PhpSpreadsheet/PHPMailer was ever executed in the sandbox this was built in; see that file before deploying). |

## Quick start

```bash
composer install
cp .env.example .env            # then edit .env with real DB/mail credentials
mysql -u root -p -e "CREATE DATABASE physioweb CHARACTER SET utf8mb4"
mysql -u root -p physioweb < database/schema.mysql.sql
mysql -u root -p physioweb < database/seed.mysql.sql   # demo data; skip in production
```

Point your web server's document root at `public/` (not the project root
&mdash; see `docs/INSTALL.md` for why and for Apache/Nginx config examples).

Demo logins (from `database/seed.mysql.sql` &mdash; **change or remove
before deploying publicly**):

- Admin CMS: `/admin/login` &mdash; `admin@physioweb.test` / `Admin@12345`
- Patient portal: `/patient/login` &mdash; `priya.sharma@example.com` / `Patient@123`

## Running the tests

```bash
bash tests/run_all.sh
```

See [`tests/README.md`](tests/README.md) for what this does and does not
prove before relying on it as a pre-deploy gate.
