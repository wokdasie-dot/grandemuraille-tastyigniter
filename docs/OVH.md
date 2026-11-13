# OVH deployment

Point the attached domain at the Git clone folder itself (`nouveau-ti/`, or `nouveau/` if that is the clone name). Do not set the domain path to `<clone>/public`. On OVH that silently removes the Git website, and later deploys no longer update this clone.

The root `.htaccess` hides the application tree and rewrites public requests into `public/`. These URLs are the ones to use:

- `POST /gm-install.php`
- `POST /gm-maintain.php`
- `/` for the site

`/public/...` answers `301` to the same path without that prefix. `/cron.php` is not a web page: the web server reaches `public/cron.php`, which answers `404`. The hourly OVH cron must run the clone-root `cron.php` with the PHP CLI.

PHP is selected by `.ovhconfig` in this folder (the folder the domain is attached to): 8.4, `environment=production`, `http.firewall=none`, image `stable64`. OVH reads that file from the attached domain root, not from an arbitrary parent. The application accepts PHP 8.4 and 8.5. `POST /gm-install.php` with `step=diagnose` reports the PHP version actually running.

## Environment location

When the parent of the clone is writable, the installer creates `../gm-data` (a sibling of the clone). That directory holds `.env`, `secrets.env`, `storage/` and `install.lock`. A later Git deploy does not replace it.

If that parent is not writable, the same files are written to `storage/gm-data`, which is gitignored.

`bootstrap/cache` stays in the repository, including the package manifests, and must be writable by PHP. Do not run `config:cache`.

## Installer

`POST /gm-install.php` over HTTPS. The token is a form field, never a query parameter. The repository only contains its SHA-256 in `config/gm-install.php`.

Call the steps in order. Each body is `application/x-www-form-urlencoded` or `multipart/form-data`.

1. `step=requirements` and `token`
2. `step=configure` plus `token`, `app_url` (https), `db_host`, `db_port`, `db_database`, `db_username`, `db_password`, `mail_host`, `mail_port`, `mail_username`, `mail_password`, `mail_encryption` (`tls` or `ssl`), `mail_from_address`, `mail_from_name`, optional `restaurant_notification_email`, optional `resend_api_key`
3. `step=migrate`
4. `step=seed`
5. `step=admin` plus `token`, `admin_name`, `admin_username`, `admin_email`, `admin_password` (at least 10 characters)
6. `step=finish`

`step=diagnose` with the same `token` is available until `finish`. It returns the PHP version and SAPI, loaded extensions, `disable_functions`, `open_basedir`, `memory_limit`, which data directory is in use and whether it is writable, a PDO result (`ok`, `ok-tls`, or the sanitized driver error), and the tail of `install.log` and the Laravel log. Passwords, tokens, `APP_KEY` and DSNs are removed. After `finish`, diagnose answers `409` like the other steps.

A successful install step answers `OK <step>`. Wrong token, non-POST and plain HTTP are refused. A failed step answers `422` or `500` with the exception class, the sanitized message, and `file:line`.

If MySQL rejects a non-TLS connection (`require_secure_transport`), migrate retries once with TLS and records `DB_SSL=true` in the environment file. `symlink`, `proc_open` and `exec` are not required. `putenv` is disabled in the application when the host disables that function.

## Maintenance

After a Git deploy that changes PHP or migrations, `POST /gm-maintain.php` with the same `token` over HTTPS. The root `.htaccess` forwards that URL into `public/gm-maintain.php`. It runs `igniter:up --force`, `view:clear` and `cache:clear`.

## Mail

Reservation mail uses Laravel SMTP from the environment file (`MAIL_MAILER=smtp` and `MAIL_HOST`). `Reply-To` is the restaurant notification address. `QUEUE_CONNECTION=sync`, so the message is sent during the request.

## Cron

Point the hourly OVH cron at the clone-root `cron.php` with PHP 8.4 (or 8.5 if that is the CLI selected for the cron). It runs `schedule:run` and exits `404` when it is not the CLI.
