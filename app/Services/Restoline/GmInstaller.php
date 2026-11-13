<?php

declare(strict_types=1);

namespace App\Services\Restoline;

use Igniter\Main\Models\Theme;
use Igniter\System\Database\Seeds\DatabaseSeeder;
use Igniter\User\Models\User;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;
use Throwable;

require_once __DIR__.'/gm_paths.php';

final class GmInstaller
{
    private bool $booted = false;

    public function __construct(private readonly string $appRoot) {}

    public function dataDirectory(): string
    {
        return gm_preferred_data_directory($this->appRoot);
    }

    public function isInstalled(): bool
    {
        return is_file($this->dataDirectory().'/install.lock');
    }

    /**
     * @return array{php:string,extensions:list<string>}
     */
    public function requirements(): array
    {
        $missing = [];
        if (version_compare(PHP_VERSION, '8.4.1', '<')) {
            $missing[] = 'php';
        }

        foreach (['intl', 'gd', 'zip', 'bcmath', 'exif', 'pdo_mysql', 'mbstring', 'curl', 'openssl', 'tokenizer', 'xml', 'fileinfo'] as $extension) {
            if (!extension_loaded($extension)) {
                $missing[] = $extension;
            }
        }

        $cache = $this->appRoot.'/bootstrap/cache';
        if (!is_dir($cache) && !@mkdir($cache, 0750, true)) {
            $missing[] = 'bootstrap_cache';
        } elseif (!is_writable($cache)) {
            $missing[] = 'bootstrap_cache';
        }

        $data = $this->dataDirectory();
        $parent = dirname($data);
        if (!is_dir($parent)) {
            @mkdir($parent, 0750, true);
        }
        if (!is_dir($parent) || !is_writable($parent)) {
            $missing[] = 'data_directory';
        }

        if ($missing !== []) {
            throw new RuntimeException('Prérequis manquants.');
        }

        return ['php' => PHP_VERSION, 'extensions' => []];
    }

    /**
     * @param array<string, mixed> $input
     */
    public function configure(array $input): void
    {
        $this->requirements();

        $appUrl = $this->requiredHttpsUrl($input, 'app_url');
        $database = $this->requiredString($input, 'db_database');
        $username = $this->requiredString($input, 'db_username');
        $password = $this->requiredString($input, 'db_password');
        $host = $this->requiredString($input, 'db_host');
        $port = (string)($input['db_port'] ?? '3306');
        if (!preg_match('/^\d{2,5}$/', $port)) {
            throw new RuntimeException('Port de base invalide.');
        }

        $mailHost = $this->requiredString($input, 'mail_host');
        $mailPort = (string)($input['mail_port'] ?? '587');
        if (!preg_match('/^\d{2,5}$/', $mailPort)) {
            throw new RuntimeException('Port SMTP invalide.');
        }
        $mailEncryption = strtolower(trim((string)($input['mail_encryption'] ?? 'tls')));
        if (!in_array($mailEncryption, ['tls', 'ssl', ''], true)) {
            throw new RuntimeException('Chiffrement SMTP invalide.');
        }
        $mailFrom = $this->requiredEmail($input, 'mail_from_address');
        $mailFromName = trim((string)($input['mail_from_name'] ?? "La Grande Muraille de Chine"));
        if ($mailFromName === '') {
            $mailFromName = "La Grande Muraille de Chine";
        }
        $notify = trim((string)($input['restaurant_notification_email'] ?? 'jinzhu1031@gmail.com'));
        if (!filter_var($notify, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('E-mail de notification invalide.');
        }

        $directory = $this->dataDirectory();
        $this->makeDataTree($directory);

        $existingKey = $this->existingAppKey($directory.'/.env');
        $appKey = $existingKey ?? 'base64:'.base64_encode(random_bytes(32));

        $lines = [
            $this->envLine('APP_NAME', 'La Grande Muraille de Chine'),
            $this->envLine('APP_ENV', 'production'),
            $this->envLine('APP_KEY', $appKey),
            $this->envLine('APP_DEBUG', 'false'),
            $this->envLine('APP_URL', $appUrl),
            $this->envLine('APP_LOCALE', 'fr'),
            $this->envLine('APP_FALLBACK_LOCALE', 'en'),
            $this->envLine('IGNITER_LOCATION_MODE', 'multiple'),
            $this->envLine('DB_CONNECTION', 'mysql'),
            $this->envLine('DB_HOST', $host),
            $this->envLine('DB_PORT', $port),
            $this->envLine('DB_DATABASE', $database),
            $this->envLine('DB_USERNAME', $username),
            $this->envLine('DB_PASSWORD', $password),
            $this->envLine('DB_PREFIX', ''),
            $this->envLine('BROADCAST_DRIVER', 'log'),
            $this->envLine('CACHE_DRIVER', 'file'),
            $this->envLine('QUEUE_CONNECTION', 'sync'),
            $this->envLine('SESSION_DRIVER', 'file'),
            $this->envLine('SESSION_LIFETIME', '120'),
            $this->envLine('MAIL_MAILER', 'smtp'),
            $this->envLine('MAIL_HOST', $mailHost),
            $this->envLine('MAIL_PORT', $mailPort),
            $this->envLine('MAIL_USERNAME', (string)($input['mail_username'] ?? '')),
            $this->envLine('MAIL_PASSWORD', (string)($input['mail_password'] ?? '')),
            $this->envLine('MAIL_ENCRYPTION', $mailEncryption === '' ? 'null' : $mailEncryption),
            $this->envLine('MAIL_FROM_ADDRESS', $mailFrom),
            $this->envLine('MAIL_FROM_NAME', $mailFromName),
            $this->envLine('RESTAURANT_NOTIFICATION_EMAIL', $notify),
            $this->envLine('RESTOLINE_PRIVATE_ENV', $directory.'/secrets.env'),
            $this->envLine('LOG_CHANNEL', 'daily'),
        ];

        $this->writeSecret($directory.'/.env', implode("\n", $lines)."\n");

        $secrets = ["# Private environment for this instance.\n", "# Do not commit this file.\n"];
        $resend = trim((string)($input['resend_api_key'] ?? ''));
        if ($resend !== '') {
            $secrets[] = $this->envLine('RESEND_API_KEY', $resend)."\n";
            $secrets[] = $this->envLine('RESEND_FROM_EMAIL', $mailFrom)."\n";
            $secrets[] = $this->envLine('RESEND_FROM_NAME', $mailFromName)."\n";
        }
        $this->writeSecret($directory.'/secrets.env', implode('', $secrets));
    }

    public function migrate(): void
    {
        if (!is_file($this->dataDirectory().'/.env')) {
            throw new RuntimeException('Configuration manquante.');
        }

        $this->boot();

        DatabaseSeeder::$seedDemo = false;
        DatabaseSeeder::$seedInitial = true;
        DatabaseSeeder::$siteName = 'La Grande Muraille de Chine';
        DatabaseSeeder::$siteUrl = (string)config('app.url');
        DatabaseSeeder::$siteEmail = (string)env('MAIL_FROM_ADDRESS', 'jinzhu1031@gmail.com');
        DatabaseSeeder::$password = bin2hex(random_bytes(24));

        $code = Artisan::call('igniter:up', ['--force' => true]);
        if ($code !== 0) {
            throw new RuntimeException('Migrations interrompues.');
        }
    }

    public function seed(): void
    {
        $this->boot();
        $code = Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\GrandeMurailleSeeder',
            '--force' => true,
        ]);
        if ($code !== 0) {
            throw new RuntimeException('Données restaurant interrompues.');
        }
    }

    /**
     * @param array<string, mixed> $input
     */
    public function createAdmin(array $input): void
    {
        $this->boot();

        $name = $this->requiredString($input, 'admin_name');
        $username = $this->requiredString($input, 'admin_username');
        $email = $this->requiredEmail($input, 'admin_email');
        $password = (string)($input['admin_password'] ?? '');
        if (strlen($password) < 10) {
            throw new RuntimeException('Mot de passe admin trop court.');
        }

        $existing = User::query()->where('username', $username)->orWhere('email', $email)->first();
        if ($existing) {
            throw new RuntimeException('Ce compte admin existe déjà.');
        }

        $languageId = null;
        if (\Illuminate\Support\Facades\Schema::hasTable('languages')) {
            $languageId = \Illuminate\Support\Facades\DB::table('languages')->where('code', 'zh_CN')->value('language_id')
                ?? \Illuminate\Support\Facades\DB::table('languages')->where('code', 'fr')->value('language_id');
        }

        $user = new User();
        $user->forceFill([
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'super_user' => true,
            'is_activated' => true,
            'status' => true,
            'activated_at' => now(),
            'language_id' => $languageId,
            'send_invite' => false,
        ]);
        $user->save();

        $locationId = \Illuminate\Support\Facades\DB::table('locations')->orderBy('location_id')->value('location_id');
        if ($locationId && method_exists($user, 'locations')) {
            $user->locations()->syncWithoutDetaching([(int)$locationId]);
        }
    }

    public function finish(): void
    {
        $this->boot();

        try {
            Artisan::call('igniter:theme-vendor-publish');
            Theme::syncAll();
            Theme::activateTheme('restoline-wokdasie', true);
        } catch (Throwable) {
            $this->log('theme activation skipped');
        }

        $linked = false;
        if (function_exists('symlink')) {
            try {
                Artisan::call('storage:link');
                $linked = is_link($this->appRoot.'/public/storage');
            } catch (Throwable) {
                $linked = false;
            }
        }
        if (!$linked) {
            $media = $this->appRoot.'/public/media';
            if (!is_dir($media)) {
                mkdir($media, 0750, true);
            }
        }

        Artisan::call('view:clear');
        Artisan::call('cache:clear');

        $lock = $this->dataDirectory().'/install.lock';
        $this->writeSecret($lock, 'installed '.gmdate('c')."\n");
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $app = require $this->appRoot.'/bootstrap/app.php';
        $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();
        $this->booted = true;
    }

    private function makeDataTree(string $directory): void
    {
        $paths = [
            $directory,
            $directory.'/storage/app/public/media/uploads',
            $directory.'/storage/app/private',
            $directory.'/storage/framework/cache/data',
            $directory.'/storage/framework/sessions',
            $directory.'/storage/framework/views',
            $directory.'/storage/logs',
        ];
        foreach ($paths as $path) {
            if (!is_dir($path) && !mkdir($path, 0750, true) && !is_dir($path)) {
                throw new RuntimeException('Dossier de données inaccessible.');
            }
        }
        @chmod($directory, 0750);
    }

    private function existingAppKey(string $path): ?string
    {
        if (!is_file($path)) {
            return null;
        }
        $contents = (string)file_get_contents($path);
        if (preg_match('/^APP_KEY="?([^"\r\n]+)"?/m', $contents, $match) === 1 && str_starts_with($match[1], 'base64:')) {
            return $match[1];
        }

        return null;
    }

    /**
     * @param array<string, mixed> $input
     */
    private function requiredString(array $input, string $key): string
    {
        $value = trim((string)($input[$key] ?? ''));
        if ($value === '' || str_contains($value, "\n") || str_contains($value, "\r") || str_contains($value, "\0")) {
            throw new RuntimeException('Champ manquant.');
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $input
     */
    private function requiredEmail(array $input, string $key): string
    {
        $value = $this->requiredString($input, $key);
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('E-mail invalide.');
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $input
     */
    private function requiredHttpsUrl(array $input, string $key): string
    {
        $value = $this->requiredString($input, $key);
        $parts = parse_url($value);
        if (!is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])) {
            throw new RuntimeException('APP_URL doit être en https.');
        }

        return rtrim($value, '/');
    }

    private function envLine(string $key, string $value): string
    {
        $escaped = str_replace(['\\', '"'], ['\\\\', '\\"'], $value);

        return $key.'="'.$escaped.'"';
    }

    private function writeSecret(string $path, string $contents): void
    {
        if (file_put_contents($path, $contents, LOCK_EX) === false) {
            throw new RuntimeException('Écriture impossible.');
        }
        @chmod($path, 0600);
    }

    private function log(string $message): void
    {
        $path = $this->dataDirectory().'/install.log';
        @file_put_contents($path, gmdate('c').' '.$message."\n", FILE_APPEND | LOCK_EX);
    }
}
