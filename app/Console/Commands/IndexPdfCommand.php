<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pratica;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class IndexPdfCommand extends Command
{
    protected $signature = 'pdf:index';
    protected $description = 'Indicizza tutti i PDF delle pratiche con OCR, hashing, timeout e controlli anti-blocco';

    public function handle()
    {
        $this->info("📄 Avvio indicizzazione PDF...");

        $pratiche = Pratica::all();
        $this->info("Trovate " . $pratiche->count() . " pratiche.");

        $bar = $this->output->createProgressBar($pratiche->count());
        $bar->start();

        foreach ($pratiche as $p) {

            try {

                $cartellaPath = storage_path("app/public/PELAGO/PDF/" . $p->cartella);

                if (!File::exists($cartellaPath)) {
                    $bar->advance();
                    continue;
                }

                $files = File::allFiles($cartellaPath);

                foreach ($files as $file) {

                    $filename = $file->getFilename();
                    $relative = $file->getRelativePathname();
                    $fullPath = $file->getRealPath();

                    $this->info("\n➡️ Pratica {$p->id} — file: $filename");

                    // Skip se non PDF
                    if (strtolower($file->getExtension()) !== 'pdf') {
                        $this->line("   ⏭ Non è PDF, salto.");
                        continue;
                    }

                    // HASH per saltare file già indicizzati
                    $hash = md5_file($fullPath);
                    $this->line("   🔍 Hash: $hash");

                    $existing = DB::table('pdf_index')
                        ->where('pratica_id', $p->id)
                        ->where('file', $relative)
                        ->first();

                    if ($existing && $existing->hash === $hash) {
                        $this->line("   ✔ Già indicizzato (hash identico) → skip");
                        continue;
                    }

                    $escapedFullPath = escapeshellarg($fullPath);

                    // 1️⃣ Estrazione testo normale
                    $this->line("   📘 Estrazione testo (pdftotext)");
                    $text = trim(shell_exec("pdftotext $escapedFullPath -"));

                    // 2️⃣ Se testo vuoto → PDF immagine → OCR
                    if (strlen($text) < 20) {
                        $this->line("   ❗ Sembra PDF immagine → OCR");

                        $tmp = storage_path("app/tmp_ocr_" . uniqid());
                        $escapedTmp = escapeshellarg($tmp);

                        $this->line("   📤 Estraggo immagine (timeout 10s)...");
                        $output = shell_exec("timeout 10s pdftoppm -singlefile -png $escapedFullPath $escapedTmp 2>&1");

                        $imgPath = $tmp . ".png";

                        if (!file_exists($imgPath)) {
                            $this->error("   ❌ Immagine non generata (errore o timeout: " . trim($output) . ")");
                            continue;
                        }

                        // Se immagine troppo grande → skip OCR
                        if (filesize($imgPath) > 10 * 1024 * 1024) {
                            $this->error("   🚫 Immagine > 10MB → skip OCR");
                            unlink($imgPath);
                            continue;
                        }

                        // 3️⃣ OCR
                        $this->line("   🔠 OCR con Tesseract...");
                        $escapedImg = escapeshellarg($imgPath);

                        $ocrOutput = shell_exec("tesseract $escapedImg stdout -l ita --psm 6 2>/dev/null");
                        $text = trim($ocrOutput);

                        unlink($imgPath);

                        $this->line("   📄 OCR ottenuto: " . substr($text, 0, 80) . "...");
                    } else {
                        $this->line("   📄 Testo estratto: " . substr($text, 0, 80) . "...");
                    }

                    // 4️⃣ Salvataggio su DB
                    DB::table('pdf_index')->updateOrInsert(
                        [
                            'pratica_id' => $p->id,
                            'file'       => $relative,
                        ],
                        [
                            'content'    => $text,
                            'hash'       => $hash,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );

                    $this->line("   💾 Salvato nel DB.");
                }

            } catch (\Throwable $e) {
                $this->error("\n❌ ERRORE nella pratica {$p->id}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->info("\n\n✅ Indicizzazione completata senza blocchi!");

        return Command::SUCCESS;
    }
}
