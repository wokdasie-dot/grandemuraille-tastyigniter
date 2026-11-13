<?php

declare(strict_types=1);

// OVH cron runs this file with the PHP CLI. It must not be reachable on the web.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('LARAVEL_START', microtime(true));

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$status = Illuminate\Support\Facades\Artisan::call('schedule:run');
fwrite(STDOUT, Illuminate\Support\Facades\Artisan::output());

exit($status);
