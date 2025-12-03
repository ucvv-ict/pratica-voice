<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PdfIndex;
use App\Models\PdfTextPage;
use App\Models\PdfAiClassification;
use App\Models\PdfAiEmbedding;
use Illuminate\Support\Facades\File;
use OpenAI;

class PdfAiIndexCommand extends Command
{
    protected $signature = 'pdf:ai-index {--limit=1}';
    protected $description = 'OCR + classificazione + embeddings dei PDF usando OpenAI Vision';

    public function handle()
    {
        $limit = (int) $this->option('limit');

        $this->info("🚀 Avvio indicizzazione AI (limit = {$limit})\n");

        //
        // 1️⃣ Troviamo i PDF da elaborare usando pdf_index
        //

        if ($limit === -1) {
            // TUTTI
            $records = PdfIndex::orderBy('id')->get();
        } else {
            // SOLO quelli non analizzati (nessuna pagina OCR)
            $records = PdfIndex::whereNotIn('id', function ($q) {
                $q->select('pdf_index_id')->from('pdf_text_pages');
            })->orderBy('id')->take($limit)->get();
        }

        if ($records->count() === 0) {
            $this->info("🎉 Nulla da fare: tutti i PDF sono già stati analizzati.");
            return Command::SUCCESS;
        }

        $client = OpenAI::client(env('OPENAI_API_KEY'));

        foreach ($records as $pdf) {

            $this->info("\n=========================================");
            $this->info("📄 PDF ID {$pdf->id} — {$pdf->file}");
            $this->info("Pratica: {$pdf->pratica_id}");
            $this->info("=========================================");

            //
            // 2️⃣ Determiniamo percorso file
            //
            $folder = rtrim(config('pratica.pdf_base_path'), '/') . '/' . $pdf->pratica->cartella;
            $path   = $folder . '/' . $pdf->file;

            if (!file_exists($path)) {
                $this->error("❌ File non trovato: $path");
                continue;
            }

            //
            // 3️⃣ Quante pagine ci sono?
            //
            $pageCount = $this->countPdfPages($path);
            $this->info("➡️ Numero pagine: {$pageCount}");

            //
            // 4️⃣ Elaboriamo pagina per pagina
            //
            for ($page = 1; $page <= $pageCount; $page++) {

                // Se già elaborata → skip
                $already = PdfTextPage::where('pdf_index_id', $pdf->id)
                                       ->where('page', $page)
                                       ->exists();

                if ($already) {
                    $this->line("🔁 Pagina $page già OCR → skip");
                    continue;
                }

                $this->line("\n🔍 Pagina $page → estrazione immagine...");

                //
                // Estrai pagina come PNG
                //
                $tmp = storage_path("app/tmp_p{$pdf->id}_{$page}");
                $cmd = "pdftoppm -f {$page} -l {$page} -png " . escapeshellarg($path) . " " . escapeshellarg($tmp);
                shell_exec($cmd);
                $imgPath = $tmp . "-1.png"; // pdftoppm aggiunge "-1"

                if (!file_exists($imgPath)) {
                    $this->error("❌ Errore: immagine pagina $page non generata!");
                    continue;
                }

                //
                // 5️⃣ OCR tramite OpenAI Vision
                //
                $this->line("🧠 OCR pagina $page con OpenAI Vision...");

                $response = $client->chat()->create([
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                ['type' => 'text', 'text' => "Effettua OCR completo, preciso e senza aggiunte."],
                                ['type' => 'image_url', 'image_url' => [
                                    'url' => 'data:image/png;base64,' . base64_encode(file_get_contents($imgPath))
                                ]]
                            ]
                        ]
                    ]
                ]);

                $text = trim($response->choices[0]->message->content ?? "");

                unlink($imgPath);

                //
                // 6️⃣ Salviamo l'OCR
                //
                PdfTextPage::create([
                    'pdf_index_id' => $pdf->id,
                    'page'         => $page,
                    'text_ocr'     => $text,
                ]);

                $this->line("✔ OCR salvato.");


                //
                // 7️⃣ Classificazione AI
                //
                $this->line("🧠 Classificazione pagina $page...");

                // Recuperiamo i metadati della pratica
                $p = $pdf->pratica;

                $msg = "
Contesto della pratica edilizia:
- Tipo pratica: {$p->sigla_tipo_pratica}
- Oggetto: {$p->oggetto}
- Indirizzo: {$p->area_circolazione} {$p->civico_esponente}
- Foglio catastale: {$p->foglio}
- Richiedente: {$p->rich_cognome1} {$p->rich_nome1}
- Anno: {$p->anno_presentazione}

Testo estratto della pagina:
{$text}

Restituisci SOLO un JSON con:
{
  \"temi\": [...],
  \"tipo_documento\": \"...\",
  \"persone_menzionate\": [...],
  \"indirizzi_menzionati\": [...],
  \"riferimenti_temporali\": [...],
  \"tags\": [...],
  \"riassunto\": \"...\"
}
                ";

                $classification = $client->chat()->create([
                    'model' => 'gpt-4o-mini',
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'user', 'content' => $msg]
                    ]
                ]);

                $json = json_decode($classification->choices[0]->message->content ?? "{}", true);

                PdfAiClassification::create([
                    'pdf_index_id' => $pdf->id,
                    'page'         => $page,
                    'classification'=> $json,
                    'summary'      => $json['riassunto'] ?? null,
                ]);

                $this->line("✔ Classificazione salvata.");


                //
                // 8️⃣ Embeddings
                //
                $this->line("🔷 Embedding pagina $page...");

                $emb = $client->embeddings()->create([
                    'model' => 'text-embedding-3-large',
                    'input' => $text,
                ]);

                PdfAiEmbedding::create([
                    'pdf_index_id' => $pdf->id,
                    'page'         => $page,
                    'embedding'    => $emb->data[0]->embedding,
                ]);

                $this->line("✔ Embedding salvato.");
            }

            $this->info("🎉 PDF {$pdf->file} COMPLETATO");
        }

        return Command::SUCCESS;
    }

    private function countPdfPages($path)
    {
        $cmd = "pdfinfo " . escapeshellarg($path) . " | grep Pages | awk '{print $2}'";
        return (int) trim(shell_exec($cmd));
    }
}
