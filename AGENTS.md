# La Grande Muraille de Chine

Reservation-only instance. Feature flags for takeaway, QR ordering, SMS, telephony, gift cards, marketing, feedback and the service API stay off.

Do not commit `.env`, database exports, logs, or the plaintext installer token. `config/gm-install.php` stores only a SHA-256 hash.

Do not run `php artisan config:cache`. `PrivateEnvironment` reads `env()` at runtime, and there is no fallback path to another restaurant's secrets file.

Mail on this deployment is SMTP, from the values written by the installer into the environment file outside the Git tree.
