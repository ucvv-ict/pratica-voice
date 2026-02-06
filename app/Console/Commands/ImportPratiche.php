<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pratica;

class ImportPratiche extends Command
{
    protected $signature = 'pratiche:import {file}';
    protected $description = 'Importa o aggiorna pratiche da CSV (idempotente)';

    public function handle()
    {
        $file = $this->argument('file');

        if (!file_exists($file)) {
            $this->error("File non trovato: $file");
            return Command::FAILURE;
        }

        $fh = fopen($file, 'r');
        if (!$fh) {
            $this->error("Impossibile aprire il file CSV.");
            return Command::FAILURE;
        }

        // --- 1) Leggi header completo ---
        $rawHeader = fgetcsv($fh, 0, ';');

        // Colonna chiave
        $praticaIdIndex = array_search('PraticaID', $rawHeader);
        if ($praticaIdIndex === false) {
            $this->error("PraticaID non trovato nell'header!");
            return Command::FAILURE;
        }

        // Colonna BAT (opzionale)
        $batIndex = array_search('BAT', $rawHeader);

        // Header pulito: SOLO fino a PraticaID
        $cleanHeader = array_slice($rawHeader, 0, $praticaIdIndex + 1);

        $count = 0;

        // --- 2) Leggi righe ---
        while (($fullRow = fgetcsv($fh, 0, ';')) !== false) {

            // Riga pulita per i metadati classici
            $row = array_slice($fullRow, 0, count($cleanHeader));
            $row = array_pad($row, count($cleanHeader), null);

            $data = @array_combine($cleanHeader, $row);
            if (!$data || empty($data['PraticaID'])) {
                continue;
            }

            // --- 3) Payload pratiche ---
            $payload = [
                'anno_presentazione'   => $data['AnnoPresentazione'] ?? null,
                'data_protocollo'      => $data['DataProtocollo'] ?? null,
                'numero_protocollo'    => $data['NumeroProtocollo'] ?? null,
                'oggetto'              => $data['Oggetto'] ?? null,
                'numero_pratica'       => $data['NumeroPratica'] ?? null,
                'data_rilascio'        => $data['DataRilascio'] ?? null,
                'numero_rilascio'      => $data['NumeroRilascio'] ?? null,
                'riferimento_libero'   => $data['RiferimentoLibero'] ?? null,
                'area_circolazione'    => $data['AreaCircolazione'] ?? null,
                'civico_esponente'     => $data['CivicoEsponente'] ?? null,
                'codice_catasto'       => $data['CodiceCatasto'] ?? null,
                'tipo_catasto'         => $data['TipoCatasto'] ?? null,
                'sezione'              => $data['Sezione'] ?? null,
                'foglio'               => $data['Foglio'] ?? null,
                'particella_sub'       => $data['ParticellaSub'] ?? null,
                'nota'                 => $data['Nota'] ?? null,
                'rich_cognome1'        => $data['RichiedenteCognome1'] ?? null,
                'rich_nome1'           => $data['RichiedenteNome1'] ?? null,
                'rich_cognome2'        => $data['RichiedenteCognome2'] ?? null,
                'rich_nome2'           => $data['RichiedenteNome2'] ?? null,
                'rich_cognome3'        => $data['RichiedenteCognome3'] ?? null,
                'rich_nome3'           => $data['RichiedenteNome3'] ?? null,
                'sigla_tipo_pratica'   => $data['SiglaTipoPratica'] ?? null,
                'cartella'             => $data['PraticaID'],
            ];

            // --- 4) BAT → gruppo_bat ---
            if ($batIndex !== false && isset($fullRow[$batIndex]) && trim($fullRow[$batIndex]) !== '') {
                $payload['gruppo_bat'] = trim($fullRow[$batIndex]);
            }

            // --- 5) Upsert idempotente ---
            Pratica::updateOrCreate(
                ['pratica_id' => $data['PraticaID']],
                $payload
            );

            // --- 6) Output diagnostico ---
            $this->line("✔ {$data['PraticaID']} | BAT: " . ($payload['gruppo_bat'] ?? '-'));

            $count++;
        }

        fclose($fh);

        $this->info("✔ Importazione completata (idempotente): $count righe processate.");

        return Command::SUCCESS;
    }
}
