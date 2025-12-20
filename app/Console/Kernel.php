<?php

namespace App\Console;

use App\Console\Commands\ExpireTimeWindows;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        ExpireTimeWindows::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // Ejecutar cada 5 minutos (puedes ajustarlo)
        $schedule->command('timewindows:expire')->everyFiveMinutes();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
