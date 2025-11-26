<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pratica;

class ImportPratiche extends Command
{
    protected $signature = 'pratiche:import {file}';
    protected $description = 'Importa pratiche dal CSV ignorando colonne extra';

    public function handle()
    {
        $file = $this->argument('file');

        if (!file_exists($file)) {
            $this->error("File non trovato: $file");
            return;
        }

        $fh = fopen($file, 'r');

        if (!$fh) {
            $this->error("Impossibile aprire il file CSV.");
            return;
        }

        // --- 1) Leggi header completo ---
        $rawHeader = fgetcsv($fh, 0, ';');

        // Trova la posizione della colonna PraticaID
        $praticaIdIndex = array_search('PraticaID', $rawHeader);

        if ($praticaIdIndex === false) {
            $this->error("PraticaID non trovato nell'header!");
            return;
        }

        // Usa SOLO le prime colonne fino a PraticaID
        $cleanHeader = array_slice($rawHeader, 0, $praticaIdIndex + 1);

        $count = 0;

        // --- 2) Leggi tutte le righe ---
        while (($row = fgetcsv($fh, 0, ';')) !== false) {

            // Taglia la riga alle stesse colonne dell'header pulito
            $row = array_slice($row, 0, count($cleanHeader));

            // Pad se la riga è più corta (alcune hanno campi vuoti)
            $row = array_pad($row, count($cleanHeader), null);

            // Combina header → riga
            $data = @array_combine($cleanHeader, $row);
            if (!$data) continue;

            // --- 3) Salva nel database ---
            Pratica::create([
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
                'pratica_id'           => $data['PraticaID'] ?? null,

                'cartella'             => $data['PraticaID'] ?? null,
            ]);

            $count++;
        }

        fclose($fh);

        $this->info("✔ Importazione completata: $count pratiche importate.");
    }
}

