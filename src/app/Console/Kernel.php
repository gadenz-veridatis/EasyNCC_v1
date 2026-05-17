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
        // Check for expired quotes (daily at 00:05)
        $schedule->command('quotes:check-scadenze')
            ->dailyAt('00:05')
            ->withoutOverlapping();

        // Gmail ingestion polling - runs based on company settings (default: every 60 minutes)
        $schedule->command('gmail:fetch-richieste')
            ->everyMinute()
            ->when(function () {
                // Check if it's time to run based on the minimum polling interval across all companies
                $minInterval = \App\Models\Settings::min('gmail_polling_interval') ?? 60;
                $minutes = (int) date('i') + (int) date('G') * 60;
                return $minutes % $minInterval === 0;
            })
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
