<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pratica;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class IndexPdfCommand extends Command
{
    protected $signature = 'pdf:index';
    protected $description = 'Indicizza i PDF delle pratiche tramite hash (senza OCR) con limite e skip pratiche già indicizzate';

    public function handle()
    {
        $this->info("📄 Avvio indicizzazione PDF...");

        // Limite dal .env
        $limit = config('pratica.index_limit');
        $this->info("🔢 Limite pratiche da indicizzare: $limit");

        // Pratiche NON ancora indicizzate
        $pratiche = Pratica::whereNotIn('id', function ($q) {
                $q->select('pratica_id')->from('pdf_index');
            })
            ->orderBy('id')
            ->take($limit)
            ->get();

        $this->info("📁 Pratiche da indicizzare: " . $pratiche->count());

        if ($pratiche->count() === 0) {
            $this->info("🎉 Tutte le pratiche sono già indicizzate!");
            return Command::SUCCESS;
        }

        $bar = $this->output->createProgressBar($pratiche->count());
        $bar->start();

        foreach ($pratiche as $p) {

            try {

                $cartellaPath = rtrim(config('pratica.pdf_base_path'), '/') . '/' . $p->cartella;

                if (!File::exists($cartellaPath)) {
                    $this->line("\n⚠️ Cartella mancante per pratica {$p->id}: {$p->cartella}");
                    $bar->advance();
                    continue;
                }

                $files = File::allFiles($cartellaPath);

                // Solo PDF
                $pdfFiles = array_filter($files, function ($f) {
                    return strtolower($f->getExtension()) === 'pdf';
                });

                // Salva il numero di PDF
                $p->numero_pdf = count($pdfFiles);
                $p->save();

                // Indicizzazione hash
                foreach ($pdfFiles as $file) {

                    $filename  = $file->getFilename();
                    $relative  = $file->getRelativePathname();
                    $fullPath  = $file->getRealPath();

                    $this->info("\n➡️ Pratica {$p->id} — file: $filename");

                    // HASH
                    $hash = md5_file($fullPath);
                    $this->line("   🔍 Hash: $hash");

                    // Esiste già?
                    $existing = DB::table('pdf_index')
                        ->where('pratica_id', $p->id)
                        ->where('file', $relative)
                        ->first();

                    if ($existing && $existing->hash === $hash) {
                        $this->line("   ✔ Già indicizzato (hash identico) → skip");
                        continue;
                    }

                    // Salvare hash
                    DB::table('pdf_index')->updateOrInsert(
                        [
                            'pratica_id' => $p->id,
                            'file'       => $relative,
                        ],
                        [
                            'hash'       => $hash,
                            'content'    => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );

                    $this->line("   💾 Hash salvato nel DB.");
                }

            } catch (\Throwable $e) {
                $this->error("\n❌ ERRORE nella pratica {$p->id}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->info("\n\n✅ Indicizzazione completata! (limite: $limit)");

        return Command::SUCCESS;
    }
}
