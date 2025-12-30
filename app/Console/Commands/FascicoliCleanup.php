<?php

namespace App\Console\Commands;

use App\Models\FascicoloGenerazione;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FascicoliCleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fascicoli:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Elimina zip temporanei dei fascicoli più vecchi di N giorni e azzera il percorso in DB';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) config('pratica.fascicolo_expiry_days', 3);
        $threshold = Carbon::now()->subDays($days);
        $deleted = 0;
        $cleared = 0;

        FascicoloGenerazione::whereNotNull('file_zip')
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($threshold, &$deleted, &$cleared) {
                foreach ($rows as $fascicolo) {
                    $path = $fascicolo->file_zip;

                    if (!$path) {
                        continue;
                    }

                    $shouldDelete = false;

                    if (file_exists($path)) {
                        $mtime = filemtime($path);
                        if ($mtime === false) {
                            $shouldDelete = true;
                        } else {
                            $shouldDelete = Carbon::createFromTimestamp($mtime)->lessThan($threshold);
                        }
                    } else {
                        // se il file non esiste più, puliamo il campo
                        $shouldDelete = true;
                    }

                    if ($shouldDelete && file_exists($path)) {
                        if (@unlink($path)) {
                            $deleted++;
                        } else {
                            Log::warning("Fascicoli cleanup: impossibile cancellare {$path}");
                        }
                    }

                    if ($shouldDelete) {
                        $fascicolo->update(['file_zip' => null]);
                        $cleared++;
                    }
                }
            });

        $this->info("Cleanup completato. File cancellati: {$deleted}. Record aggiornati: {$cleared}.");

        return self::SUCCESS;
    }
}
