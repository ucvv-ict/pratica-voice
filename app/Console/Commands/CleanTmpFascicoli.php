<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CleanTmpFascicoli extends Command
{
    protected $signature = 'fascicoli:clean-tmp {--days=60 : Numero di giorni da conservare}';

    protected $description = 'Cancella i file ZIP temporanei dei fascicoli più vecchi di N giorni';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        if ($days <= 0) {
            $this->error('Il numero di giorni deve essere maggiore di zero.');

            return self::FAILURE;
        }

        $directory = storage_path('app/tmp/fascicoli');

        if (! File::isDirectory($directory)) {
            $this->warn("Directory non trovata: {$directory}");

            return self::SUCCESS;
        }

        $limitTimestamp = now()->subDays($days)->timestamp;
        $deleted = 0;
        $kept = 0;
        $ignored = 0;
        $errors = 0;

        foreach (File::files($directory) as $file) {
            if (strtolower($file->getExtension()) !== 'zip') {
                $ignored++;
                continue;
            }

            if ($file->getMTime() >= $limitTimestamp) {
                $kept++;
                continue;
            }

            try {
                if (File::delete($file->getPathname())) {
                    $deleted++;
                    continue;
                }

                $errors++;
                $this->error("Errore eliminando {$file->getFilename()}: eliminazione non riuscita.");
            } catch (\Throwable $e) {
                $errors++;
                $this->error("Errore eliminando {$file->getFilename()}: {$e->getMessage()}");
            }
        }

        $this->info('Pulizia fascicoli temporanei completata.');
        $this->info("Directory: {$directory}");
        $this->info("Giorni mantenuti: {$days}");
        $this->info("File ZIP eliminati: {$deleted}");
        $this->info("File ZIP mantenuti: {$kept}");
        $this->info("File ignorati non ZIP: {$ignored}");
        $this->info("Errori: {$errors}");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
