<?php

declare(strict_types=1);

use Carbon\Carbon;
use Igniter\Local\Models\Location;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Restoline\Content\Models\ScheduleException;

$appRoot = getenv('TASTYIGNITER_ROOT');
if (!$appRoot) {
    fwrite(STDERR, "TASTYIGNITER_ROOT is required.\n");
    exit(1);
}

require $appRoot.'/vendor/autoload.php';
$app = require $appRoot.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

DB::beginTransaction();

try {
    $location = Location::getDefault();

    ScheduleException::create([
        'location_id' => $location->getKey(),
        'name' => 'Test lundi ouvert',
        'kind' => 'open',
        'start_date' => '2026-06-15',
        'end_date' => '2026-06-15',
        'opening_start' => '12:00',
        'opening_end' => '14:30',
        'evening_start' => '18:00',
        'evening_end' => '22:00',
        'status' => true,
    ]);

    ScheduleException::create([
        'location_id' => $location->getKey(),
        'name' => 'Test fermeture juillet',
        'kind' => 'closed',
        'start_date' => '2026-07-01',
        'end_date' => '2026-07-28',
        'status' => true,
    ]);

    $schedule = $location->newWorkingSchedule(Location::OPENING, 90);
    $results = [
        'lundi_12h30_ouvert' => $schedule->isOpenAt(Carbon::parse('2026-06-15 12:30')),
        'lundi_16h_ferme' => $schedule->isClosedAt(Carbon::parse('2026-06-15 16:00')),
        'vacances_08_juillet_ferme' => $schedule->isClosedAt(Carbon::parse('2026-07-08 12:30')),
    ];

    echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;

    exit(in_array(false, $results, true) ? 1 : 0);
} finally {
    DB::rollBack();
}
