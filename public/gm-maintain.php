<?php

declare(strict_types=1);

/**
 * Post-deploy maintenance. POST + HTTPS + the same hashed installer token.
 * Runs igniter:up, view:clear and cache:clear. Never caches configuration.
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

$appRoot = dirname(__DIR__);

function gm_maintain_reply(int $status, string $message): never
{
    http_response_code($status);
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store');
    echo $message;
    exit;
}

$https = $_SERVER['HTTPS'] ?? '';
$forwarded = strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
$secure = (is_string($https) && $https !== '' && strtolower($https) !== 'off')
    || $forwarded === 'https'
    || (int)($_SERVER['SERVER_PORT'] ?? 0) === 443;

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    gm_maintain_reply(405, 'POST requis.');
}
if (!$secure) {
    gm_maintain_reply(403, 'HTTPS requis.');
}

$config = require $appRoot.'/config/gm-install.php';
$expected = (string)($config['token_sha256'] ?? '');
$given = (string)($_POST['token'] ?? '');
if ($expected === '' || $given === '' || !hash_equals($expected, hash('sha256', $given))) {
    gm_maintain_reply(403, 'Refusé.');
}

require $appRoot.'/vendor/autoload.php';
$installer = new App\Services\Restoline\GmInstaller($appRoot);
if (!$installer->isInstalled()) {
    gm_maintain_reply(409, 'Installation incomplète.');
}

try {
    $installer->boot();
    $up = Illuminate\Support\Facades\Artisan::call('igniter:up', ['--force' => true]);
    Illuminate\Support\Facades\Artisan::call('view:clear');
    Illuminate\Support\Facades\Artisan::call('cache:clear');
    if ($up !== 0) {
        gm_maintain_reply(500, 'Mise à jour interrompue.');
    }
} catch (Throwable $exception) {
    $file = $exception->getFile();
    $root = dirname(__DIR__);
    if (str_starts_with($file, $root)) {
        $file = ltrim(substr($file, strlen($root)), '/');
    }
    $message = $exception::class.': '.$exception->getMessage();
    foreach ($_POST as $value) {
        if (is_string($value) && strlen($value) >= 4) {
            $message = str_replace($value, '[redacted]', $message);
        }
    }
    $message = preg_replace('/base64:[A-Za-z0-9+\/=]+/', 'base64:[redacted]', $message) ?? $message;
    $message = preg_replace('#/home/[^/\s]+#', '/home/[account]', $message) ?? $message;
    gm_maintain_reply(500, "Échec de la maintenance.\n".$message."\n".$file.':'.$exception->getLine());
}

gm_maintain_reply(200, 'OK maintenance');
