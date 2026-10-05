# Installation Guide

## 1. Requirements

- **PHP 8.3 or newer**, with these extensions (all declared in
  `composer.json`'s `require`, so `composer install` will refuse to proceed
  if any are missing): `pdo`, `pdo_mysql`, `mbstring`, `gd`, `json`.
- **MySQL 8.0+** (or a MySQL-compatible server supporting `utf8mb4`,
  `CHECK` constraints, and `ENUM` columns as used in
  `database/schema.mysql.sql`).
- **Composer 2.x**.
- A web server (Apache with `mod_rewrite`, or Nginx) able to point its
  document root at this project's `public/` directory specifically &mdash;
  never at the project root (see &sect;4).
- Outbound network access from the server for: SMTP (email), and
  optionally the WhatsApp Cloud API / an SMS gateway if you configure
  those integrations (see &sect;6).

This guide assumes a Linux server; adjust paths/commands for other
platforms as needed.

## 2. Install dependencies

```bash
cd /path/to/physioweb
composer install --no-dev --optimize-autoloader   # add --no-dev once satisfied dev tooling isn't needed
```

This fetches `dompdf/dompdf` (PDF generation), `phpoffice/phpspreadsheet`
(Excel export), and `phpmailer/phpmailer` (email). **Important**: the
development sandbox this project was built in had no network access to
Packagist, so none of these three libraries has ever actually been
executed end-to-end here &mdash; only statically reviewed against their
documented APIs (see `tests/README.md`). Verify each one actually works in
*your* environment immediately after `composer install` completes:

```bash
# dompdf
php -r "require 'vendor/autoload.php'; \$d=new Dompdf\Dompdf(); \$d->loadHtml('<h1>Test</h1>'); \$d->render(); file_put_contents('/tmp/test.pdf', \$d->output()); echo \"PDF OK: \" . filesize('/tmp/test.pdf') . \" bytes\n\";"

# phpoffice/phpspreadsheet
php -r "require 'vendor/autoload.php'; \$s=new PhpOffice\PhpSpreadsheet\Spreadsheet(); \$s->getActiveSheet()->fromArray([['a','b'],[1,2]]); (new PhpOffice\PhpSpreadsheet\Writer\Xlsx(\$s))->save('/tmp/test.xlsx'); echo \"XLSX OK: \" . filesize('/tmp/test.xlsx') . \" bytes\n\";"
```

If either library is missing, the application does **not** crash: every
PDF/Excel export action catches the resulting `RuntimeException` and shows
the user a friendly "not available yet" message instead (see
`App\Services\PdfService` / `App\Services\ExcelService`). But exports
obviously won't work for real users until `composer install` has actually
succeeded on this server.

## 3. Configure the environment

```bash
cp .env.example .env
```

Edit `.env` and set, at minimum:

- `APP_ENV=production`, `APP_DEBUG=false`
- `APP_URL` &mdash; the real public URL (used for canonical links/structured data)
- `APP_KEY` &mdash; replace the placeholder with a random 32+ byte value,
  e.g. `php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"`. It is loaded
  into `config('app.key')` but not currently consumed by any encryption
  feature in this codebase (CSRF tokens and session IDs are generated
  independently via PHP's own `random_bytes()`/session machinery, not
  derived from `APP_KEY`); set it anyway so it's a real secret ready for
  if/when a future feature needs one, rather than the example placeholder.
- `DB_*` &mdash; your real MySQL host/database/credentials
- `MAIL_*` &mdash; your real SMTP credentials (required for appointment
  confirmation emails, invoice emailing, and patient portal communication)
- `WHATSAPP_*` / `SMS_*` &mdash; only if you have a provider; leave
  `WHATSAPP_PROVIDER=none` / `SMS_PROVIDER=none` otherwise (the default).
  See `.env.example`'s inline comments for the exact provider values
  `App\Services\NotificationService` currently implements.

`app/Core/Env` loads `.env` and `config/config.php` assembles everything
into the single config array every other file reads from &mdash; see that
file's own doc comment.

## 4. Point the web server at `public/`

The project root contains a defense-in-depth `.htaccess` (`Require all
denied`) specifically so that misconfiguring the document root to the
project root instead of `public/` fails closed rather than exposing
`app/`, `config/.env`, `storage/`, etc. directly over HTTP. Don't rely on
that alone &mdash; configure the document root correctly.

**Apache** (`public/.htaccess` already handles the front-controller
rewrite; just point the vhost at the right directory):

```apache
<VirtualHost *:443>
    ServerName clinic.example.com
    DocumentRoot /path/to/physioweb/public
    <Directory /path/to/physioweb/public>
        AllowOverride All
        Require all granted
    </Directory>
    # ... SSL config ...
</VirtualHost>
```

**Nginx** (PHP-FPM):

```nginx
server {
    listen 443 ssl;
    server_name clinic.example.com;
    root /path/to/physioweb/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ /\. {
        deny all;
    }
}
```

## 5. Database setup

```bash
mysql -u root -p -e "CREATE DATABASE physioweb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p -e "CREATE USER 'physioweb_user'@'localhost' IDENTIFIED BY 'a-real-password';"
mysql -u root -p -e "GRANT ALL PRIVILEGES ON physioweb.* TO 'physioweb_user'@'localhost';"
mysql -u root -p physioweb < database/schema.mysql.sql
```

Load demo data **only** on a staging/demo install, never in production:

```bash
mysql -u root -p physioweb < database/seed.mysql.sql
```

See [`docs/DATABASE.md`](DATABASE.md) for what every table is for.

## 6. File permissions

The web server user (e.g. `www-data`) needs write access to:

```
storage/uploads/        (patient documents -- served only via an auth-checked controller)
storage/logs/           (App\Core\Logger output)
storage/cache/
public/uploads/         (staff photos, gallery images, blog images, site logo/branding -- directly browser-viewable)
tests/tmp/              (only needed if you run the test suite as that user)
```

`App\Core\FileUpload::store()` creates its destination subdirectory on
first use if it doesn't already exist (`mkdir(..., 0775, true)`), but that
only works if the web server user can already write to its *parent*
directory &mdash; so pre-create and `chown` `public/uploads/` too, rather
than relying on that fallback for the very first upload:

```bash
mkdir -p public/uploads
chown -R www-data:www-data storage/ public/uploads/
chmod -R 775 storage/ public/uploads/
```

Nothing under `public/uploads/` vs `storage/uploads/` is accidental: images
meant to be directly browser-viewable (staff photos, gallery images, blog
images, the site logo) are written under `public/uploads/`; anything that
must only be reachable through an authorization check (patient documents)
is written under `storage/uploads/`, **outside** the web root, and served
through `Admin\DocumentController` / `Patient\DocumentController` (see
`App\Core\FileUpload`'s class-level doc comment). Keep that separation if
you ever change the upload paths.

## 7. First login and next steps

1. Log in at `/admin/login` with the admin account created by
   `database/seed.mysql.sql` (or create your own `users` row directly in
   MySQL with a bcrypt-hashed password if you skipped the seed data).
2. **Change the demo admin password immediately** (Admin &rarr; CMS Users),
   or better, delete the demo admin user after creating a real one.
3. Go to Admin &rarr; Site Settings and replace the demo clinic
   name/address/phone/logo with the real ones if you loaded the seed data
   for its structure but not its content.
4. Review [`docs/SECURITY.md`](SECURITY.md)'s production-hardening
   checklist before this is reachable by the public internet.
5. Run `bash tests/run_all.sh` on this server (not just in development) to
   confirm the full suite still passes against your actual PHP version and
   extensions &mdash; then read [`tests/README.md`](../tests/README.md) for
   what that run does *not* prove (real MySQL, real dompdf/PhpSpreadsheet
   output, real email delivery, real HTTP routing all still need a manual
   check; the guide lists exactly what to click through).

## 8. Scheduled tasks

There is **no cron-based job** in this codebase today (e.g. no automatic
"appointment reminder 24 hours before" sender). `App\Services\NotificationService`
can send a confirmation email/WhatsApp/SMS at booking time, and staff can
message a patient on demand, but nothing fires on a schedule. If you need
automated reminders, that is a feature to add (a scheduled script calling
`NotificationService` for appointments in a lookahead window), not
something already wired up and merely undocumented &mdash; don't assume
it exists.
