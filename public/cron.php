<?php

declare(strict_types=1);

// The scheduled task is cron.php at the clone root, and it is CLI-only.
// This stub stops /cron.php and /public/cron.php from entering Laravel.
http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
header('X-Robots-Tag: noindex');
echo "Introuvable.\n";
