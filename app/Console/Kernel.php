<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // OVH shared hosting runs schedule:run at most once an hour, at a minute
        // it chooses. everyMinute() is due on that single invocation.
        $schedule->command('restoline:reconcile-email-notifications --hours=3')
            ->everyMinute()
            ->withoutOverlapping();

        $schedule->command('restoline:send-reservation-feedback --limit=30')
            ->everyMinute()
            ->between('10:00', '10:59')
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
