<?php

declare(strict_types=1);

/**
 * Resolves the directory that holds .env and storage outside the Git tree
 * when the parent of the application is writable (OVH: the hosting home,
 * with the clone in nouveau/ and the docroot at nouveau/public).
 *
 * Locally, when that parent is not writable, the fallback is storage/gm-data,
 * which is gitignored so a checkout cannot commit it.
 */
function gm_preferred_data_directory(string $appRoot): string
{
    $parent = dirname($appRoot);
    if ($parent !== '/' && $parent !== '' && $parent !== '.' && is_writable($parent)) {
        return $parent.'/gm-data';
    }

    return $appRoot.'/storage/gm-data';
}

function gm_resolve_data_directory(string $appRoot): ?string
{
    $candidates = [
        dirname($appRoot).'/gm-data',
        $appRoot.'/storage/gm-data',
    ];

    foreach ($candidates as $directory) {
        if (is_file($directory.'/.env')) {
            return $directory;
        }
    }

    return null;
}

/**
 * MySQL 8.4 on OVH may require TLS. PHP 8.5 deprecates PDO::MYSQL_ATTR_*,
 * so the modern Pdo\Mysql constants are used when they exist.
 */
function gm_mysql_pdo_constant(string $suffix): ?int
{
    $modern = 'Pdo\\Mysql::'.$suffix;
    if (defined($modern)) {
        return (int)constant($modern);
    }

    if (PHP_VERSION_ID < 80500) {
        $legacy = 'PDO::MYSQL_'.$suffix;
        if (defined($legacy)) {
            return (int)constant($legacy);
        }
    }

    return null;
}

/**
 * @return array<int, mixed>
 */
function gm_mysql_pdo_options(): array
{
    if (!extension_loaded('pdo_mysql')) {
        return [];
    }

    $options = [];
    $useSsl = function_exists('env') && filter_var((string)env('DB_SSL', ''), FILTER_VALIDATE_BOOL);
    $sslCa = function_exists('env') ? env('MYSQL_ATTR_SSL_CA') : null;
    $ca = gm_mysql_pdo_constant('ATTR_SSL_CA');
    $verify = gm_mysql_pdo_constant('ATTR_SSL_VERIFY_SERVER_CERT');

    if ($useSsl && $verify !== null) {
        $options[$verify] = false;
    }

    if (is_string($sslCa) && $sslCa !== '' && $ca !== null) {
        $options[$ca] = $sslCa;
    } elseif ($useSsl && $ca !== null) {
        $bundle = '/etc/ssl/certs/ca-certificates.crt';
        if (is_file($bundle)) {
            $options[$ca] = $bundle;
        }
    }

    return $options;
}
