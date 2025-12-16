<?php

namespace App\Jobs;

use App\Models\FascicoloGenerazione;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class GeneraFascicoloJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Tenta per 1 volta; la generazione può essere lunga ma non vogliamo retry doppi.
     */
    public $tries = 1;

    /**
     * Conserviamo solo l'ID per non serializzare relazioni pesanti.
     */
    private int $fascicoloId;

    /**
     * Timeout esteso per fascicoli voluminosi (20 minuti).
     */
    public $timeout = 1200;

    public function __construct(int $fascicoloId)
    {
        $this->onConnection('database');
        $this->fascicoloId = $fascicoloId;
    }

    public function handle(): void
    {
        $fascicolo = FascicoloGenerazione::with('pratica')->find($this->fascicoloId);
        if (!$fascicolo || !$fascicolo->pratica) {
            return;
        }

        $files = is_array($fascicolo->files_selezionati) ? $fascicolo->files_selezionati : [];

        $fascicolo->update([
            'stato'    => 'in_progress',
            'progress' => 0,
            'errore'   => null,
        ]);

        $pratica = $fascicolo->pratica;
        $baseFolder = rtrim(config('pratica.pdf_base_path'), '/') . '/' . $pratica->cartella;
        $baseFolderReal = realpath($baseFolder);

        if (!$baseFolderReal) {
            throw new \RuntimeException('Cartella pratica non trovata.');
        }

        $tmpDir = storage_path('app/tmp/fascicoli');
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $zipName = "pratica_{$pratica->id}_{$fascicolo->versione}_" . time() . ".zip";
        $zipPath = $tmpDir . '/' . $zipName;

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Impossibile creare lo ZIP.');
        }

        $total = max(count($files), 1);
        $processed = 0;
        $added = 0;

        try {
            foreach ($files as $filename) {
                $full = $baseFolder . '/' . $filename;
                $real = realpath($full);

                if (!$real || !str_starts_with($real, $baseFolderReal . DIRECTORY_SEPARATOR)) {
                    Log::warning("Fascicolo zip - path non valido o fuori cartella: {$full}");
                    $processed++;
                    $this->updateProgress($fascicolo, $processed, $total);
                    continue;
                }

                if (!file_exists($real)) {
                    Log::warning("Fascicolo zip - file non trovato: {$real}");
                    $processed++;
                    $this->updateProgress($fascicolo, $processed, $total);
                    continue;
                }

                if ($zip->addFile($real, $filename)) {
                    $added++;
                } else {
                    Log::error("Fascicolo zip - errore addFile per: {$real}");
                }

                $processed++;
                $this->updateProgress($fascicolo, $processed, $total);
            }

            $zip->close();

            if ($added === 0 || !file_exists($zipPath)) {
                throw new \RuntimeException('Nessun file valido trovato per creare lo ZIP.');
            }

            $fascicolo->update([
                'stato'    => 'completed',
                'progress' => 100,
                'file_zip' => $zipPath,
                'errore'   => null,
            ]);
        } catch (\Throwable $e) {
            $this->handleFailure($fascicolo, $zip, $zipPath, $e);
            throw $e;
        }
    }

    private function updateProgress(FascicoloGenerazione $fascicolo, int $processed, int $total): void
    {
        $progress = (int) floor(($processed / $total) * 100);
        $fascicolo->update(['progress' => $progress]);
    }

    private function handleFailure(
        FascicoloGenerazione $fascicolo,
        ZipArchive $zip,
        string $zipPath,
        \Throwable $e
    ): void {
        try {
            $zip->close();
        } catch (\Throwable $ignored) {
            // ignore
        }

        if (file_exists($zipPath)) {
            unlink($zipPath);
        }

        $fascicolo->update([
            'stato'  => 'error',
            'errore' => $e->getMessage(),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        $fascicolo = FascicoloGenerazione::find($this->fascicoloId);
        if ($fascicolo) {
            $fascicolo->update([
                'stato'  => 'error',
                'errore' => $exception->getMessage(),
            ]);
        }
    }
}
