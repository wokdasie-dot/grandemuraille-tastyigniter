# OVH deployment

Docroot: `nouveau/public` (this repository cloned into `nouveau/`).

PHP is selected by `.ovhconfig`: 8.4, `environment=production`, `http.firewall=none`, image `stable64`.

## Environment location

When the parent of the application is writable, the installer creates `../gm-data` (on the hosting account, a sibling of `nouveau`). That directory holds `.env`, `secrets.env`, `storage/` and `install.lock`. A later Git deploy does not replace it.

If that parent is not writable, the same files are written to `storage/gm-data`, which is gitignored. That path is for local checks only.

`bootstrap/cache` stays in the repository and must be writable by PHP. Do not run `config:cache`.

## Installer

`POST /gm-install.php` over HTTPS. The token is a form field, never a query parameter. The repository only contains its SHA-256 in `config/gm-install.php`.

Call the steps in order. Each body is `application/x-www-form-urlencoded` or `multipart/form-data`.

1. `step=requirements` and `token`
2. `step=configure` plus `token`, `app_url` (https), `db_host`, `db_port`, `db_database`, `db_username`, `db_password`, `mail_host`, `mail_port`, `mail_username`, `mail_password`, `mail_encryption` (`tls` or `ssl`), `mail_from_address`, `mail_from_name`, optional `restaurant_notification_email`, optional `resend_api_key`
3. `step=migrate`
4. `step=seed`
5. `step=admin` plus `token`, `admin_name`, `admin_username`, `admin_email`, `admin_password` (at least 10 characters)
6. `step=finish`

A successful step answers `OK <step>`. After `finish`, every later call answers `409`. Wrong token, non-POST and plain HTTP are refused. Failures do not echo passwords.

## Maintenance

After a Git deploy that changes PHP or migrations, `POST /gm-maintain.php` with the same `token` over HTTPS. It runs `igniter:up --force`, `view:clear` and `cache:clear`.

## Mail

Reservation mail uses Laravel SMTP from the environment file (`MAIL_MAILER=smtp` and `MAIL_HOST`). `Reply-To` is the restaurant notification address. `QUEUE_CONNECTION=sync`, so the message is sent during the request.

## Cron

`cron.php` at the repository root is CLI-only. Point the hourly OVH cron at it with PHP 8.4. It runs `schedule:run`.
