<?php

namespace App\Http\Controllers;

use App\Models\Pratica;
use App\Models\AccessoAtti;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use App\Services\CloudflareR2Service;
use ZipArchive;

class PraticaController extends Controller
{

    public function downloadZip(Request $request, $id, CloudflareR2Service $r2)
    {
        $pratica = Pratica::findOrFail($id);

        if (!$request->has('files')) {
            return back()->with('error', 'Nessun file selezionato.');
        }

        $selected = $request->input('files', []);  // array nomi file
        $useR2 = $request->boolean('r2_upload');

        // 📁 Cartella tmp
        $tmpDir = storage_path("app/tmp");
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $zipName = "pratica_{$pratica->id}_" . time() . ".zip";
        $zipPath = $tmpDir . "/" . $zipName;

        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Impossibile creare lo ZIP.');
        }

        $baseFolder = rtrim(config('pratica.pdf_base_path'), '/') . '/' . $pratica->cartella;
        $added = 0;

        foreach ($selected as $filename) {
            Log::info("ZIP - richiesto: {$filename}");

            $full = $baseFolder . '/' . $filename;

            if (!file_exists($full)) {
                Log::warning("ZIP - file non trovato: {$full}");
                continue;
            }

            if ($zip->addFile($full, $filename)) {
                $added++;
            } else {
                Log::error("ZIP - errore addFile per: {$full}");
            }
        }

        $zip->close();

        if ($added === 0 || !file_exists($zipPath)) {
            if (file_exists($zipPath)) {
                unlink($zipPath);
            }
            return back()->with('error', 'Nessun file valido trovato per creare lo ZIP.');
        }

        Log::info("ZIP creato: {$zipPath} (size: " . filesize($zipPath) . " bytes)");

        // Upload su Cloudflare R2 se richiesto
        if ($useR2) {
            if (!env('R2_BUCKET')) {
                unlink($zipPath);
                return back()->with('error', 'Cloudflare R2 non è configurato (manca R2_BUCKET/credenziali).');
            }

            try {
                $key = 'zip_pratica/' . $pratica->id . '/pratica_' . $pratica->id . '_' . time() . '.zip';
                $result = $r2->uploadAndLink($zipPath, $key);
                unlink($zipPath);

                return back()
                    ->with('success', 'Link R2 generato.')
                    ->with('r2_link', $result['url'])
                    ->with('r2_expires_at', optional($result['expires_at'])->toIso8601String());
            } catch (\Throwable $e) {
                Log::error('R2 upload fallito', [
                    'error' => $e->getMessage(),
                ]);
                unlink($zipPath);

                return back()->with('error', 'Errore durante l\'upload su R2: ' . $e->getMessage());
            }
        }

        // Download locale
        return response()->download($zipPath)->deleteFileAfterSend(true);
    }


    public function show($id)
    {
        // 📌 Carica pratica
        $pratica = Pratica::findOrFail($id);

        // 📁 Percorso cartella PDF della pratica
        $folder = rtrim(config('pratica.pdf_base_path'), '/') . '/' . $pratica->cartella;

        // 📄 Lista PDF nella cartella
        $pdfFiles = collect(File::files($folder))
            ->filter(fn($f) => strtolower($f->getExtension()) === 'pdf')
            ->map(fn($f) => [
                'name' => $f->getFilename(),
                'url'  => $this->buildPdfUrl($pratica->cartella, $f->getFilename())
            ])
            ->values()
            ->toArray();

        // 📚 Accessi agli Atti già creati
        $accessi = AccessoAtti::where('pratica_id', $pratica->id)
            ->orderBy('versione', 'desc')
            ->get();

        // 🔥 Passiamo tutto alla view
        return view('pratica.show', [
            'pratica'  => $pratica,
            'pdfFiles' => $pdfFiles,
            'accessi'  => $accessi,
        ]);
    }

    private function buildPdfUrl(string $cartella, string $file): string
    {
        return route('pdf.serve', [
            'cartella' => $cartella,
            'file'     => $file,
        ]);
    }
}
