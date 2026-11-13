<?php

declare(strict_types=1);

/**
 * One-time installer for the OVH deployment.
 * The plaintext token is never stored in this repository: only its SHA-256 is.
 * A successful finish writes install.lock and every later call is refused.
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

$appRoot = dirname(__DIR__);

function gm_install_reply(int $status, string $message): never
{
    http_response_code($status);
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Robots-Tag: noindex');
    echo $message;
    exit;
}

function gm_install_https(): bool
{
    $https = $_SERVER['HTTPS'] ?? '';
    if (is_string($https) && $https !== '' && strtolower($https) !== 'off') {
        return true;
    }

    $forwarded = strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));

    return $forwarded === 'https' || (int)($_SERVER['SERVER_PORT'] ?? 0) === 443;
}

function gm_install_rate_limit(): void
{
    $bucket = sys_get_temp_dir().'/gm-install-'.hash('sha256', (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    $now = time();
    $hits = [];
    if (is_file($bucket)) {
        $decoded = json_decode((string)file_get_contents($bucket), true);
        if (is_array($decoded)) {
            $hits = array_values(array_filter($decoded, static fn($stamp): bool => is_int($stamp) && $stamp > $now - 900));
        }
    }
    $hits[] = $now;
    file_put_contents($bucket, json_encode($hits), LOCK_EX);
    if (count($hits) > 12) {
        gm_install_reply(429, 'Trop de tentatives.');
    }
}

function gm_install_sanitize(string $message): string
{
    foreach ($_POST as $value) {
        if (is_string($value) && strlen($value) >= 4) {
            $message = str_replace($value, '[redacted]', $message);
        }
    }

    return preg_replace('/(password|token|key)=([^\s]+)/i', '$1=[redacted]', $message) ?? $message;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    gm_install_reply(405, 'POST requis.');
}

if (!gm_install_https()) {
    gm_install_reply(403, 'HTTPS requis.');
}

gm_install_rate_limit();

$config = require $appRoot.'/config/gm-install.php';
$expected = (string)($config['token_sha256'] ?? '');
$given = (string)($_POST['token'] ?? '');
if ($expected === '' || $given === '' || !hash_equals($expected, hash('sha256', $given))) {
    gm_install_reply(403, 'Refusé.');
}

require $appRoot.'/vendor/autoload.php';

$installer = new App\Services\Restoline\GmInstaller($appRoot);
if ($installer->isInstalled()) {
    gm_install_reply(409, 'Installation déjà effectuée.');
}

$step = (string)($_POST['step'] ?? '');
$allowed = ['requirements', 'configure', 'migrate', 'seed', 'admin', 'finish'];
if (!in_array($step, $allowed, true)) {
    gm_install_reply(422, 'Étape inconnue.');
}

try {
    match ($step) {
        'requirements' => $installer->requirements(),
        'configure' => $installer->configure($_POST),
        'migrate' => $installer->migrate(),
        'seed' => $installer->seed(),
        'admin' => $installer->createAdmin($_POST),
        'finish' => $installer->finish(),
    };
} catch (RuntimeException $exception) {
    gm_install_reply(422, $exception->getMessage());
} catch (Throwable $exception) {
    $log = $installer->dataDirectory().'/install.log';
    @file_put_contents(
        $log,
        gmdate('c').' '.$step.' '.gm_install_sanitize($exception::class.': '.$exception->getMessage())."\n",
        FILE_APPEND | LOCK_EX,
    );
    gm_install_reply(500, 'Échec de l\'installation.');
}

gm_install_reply(200, 'OK '.$step);
