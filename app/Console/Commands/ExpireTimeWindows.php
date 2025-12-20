<?php

namespace App\Console\Commands;

use App\Models\TimeWindow;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ExpireTimeWindows extends Command
{
    protected $signature = 'timewindows:expire';

    protected $description = 'Marca como expiradas las ventanas de tiempo cuyo rango ya terminó.';

    public function handle(): int
    {
        $now = Carbon::now();

        $hoy      = $now->toDateString();
        $horaNow  = $now->format('H:i:s');

        // Ventanas activas que ya pasaron
        $afectadas1 = TimeWindow::where('estado', 'activo')
            ->where(function ($q) use ($hoy, $horaNow) {
                $q->whereDate('fecha', '<', $hoy)
                  ->orWhere(function ($q2) use ($hoy, $horaNow) {
                      $q2->whereDate('fecha', $hoy)
                         ->where('hora_fin', '<', $horaNow);
                  });
            })
            ->update(['estado' => 'expirado']);

        // Ventanas reabiertas que ya superaron reabierto_hasta
        $afectadas2 = TimeWindow::where('estado', 'reabierto')
            ->whereNotNull('reabierto_hasta')
            ->where('reabierto_hasta', '<', $now)
            ->update(['estado' => 'expirado']);

        $this->info("Ventanas expiradas (activas): {$afectadas1}");
        $this->info("Ventanas expiradas (reabiertas): {$afectadas2}");

        return Command::SUCCESS;
    }
}
