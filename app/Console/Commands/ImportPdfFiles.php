<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PdfFile;
use App\Models\Pratica;

class ImportPdfFiles extends Command
{
    protected $signature = 'pdf:import {file : Percorso del CSV}';
    protected $description = 'Importa la lista dei PDF dal CSV con hash e metadati';

    public function handle()
    {
        $path = $this->argument('file');

        if (!file_exists($path)) {
            $this->error("File non trovato: $path");
            return 1;
        }

        $this->info("Importazione in corso...");

        $handle = fopen($path, 'r');
        $header = fgetcsv($handle, 0, ';'); // CSV separato da ;

        $count = 0;

        while (($row = fgetcsv($handle, 0, ';')) !== false) {

            [$cartella, $filename, $md5, $sha256, $size] = $row;

            // trova la pratica
            $pratica = Pratica::where('cartella', $cartella)->first();

            PdfFile::updateOrCreate(
                ['cartella' => $cartella, 'file' => $filename],
                [
                    'pratica_id'  => $pratica?->id,
                    'md5'         => $md5,
                    'sha256'      => $sha256,
                    'size_bytes'  => $size,
                ]
            );

            $count++;
            if ($count % 1000 === 0) {
                $this->info("$count file importati...");
            }
        }

        fclose($handle);

        $this->info("Importazione completata! Totale: $count file PDF");

        return 0;
    }
}
