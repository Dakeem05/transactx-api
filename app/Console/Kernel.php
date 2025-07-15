<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('app:transfer-service-command')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();
        $schedule->command('app:airtime-service-command')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();
        $schedule->command('app:data-service-command')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();
        $schedule->command('app:cabletv-service-command')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();
        $schedule->command('app:utility-service-command')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();
        $schedule->command('app:subscription-reminders-command')
            ->daily()
            ->withoutOverlapping()
            ->runInBackground();
        $schedule->command('app:subscription-expired-command')
            ->daily()
            ->withoutOverlapping()
            ->runInBackground();
        $schedule->command('app:subscription-renew-command')
            ->daily()
            ->withoutOverlapping()
            ->runInBackground();
        $schedule->command('app:subscription-revert-reminder-command')
            ->daily()
            ->withoutOverlapping()
            ->runInBackground();
        $schedule->command('app:subscription-revert-command')
            ->daily()
            ->withoutOverlapping()
            ->runInBackground();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
