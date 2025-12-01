<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\DetectOutages::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        // No cron needed because Railway loops it
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
    }
}
