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
