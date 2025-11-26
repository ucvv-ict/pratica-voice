<?php

namespace App\Http\Controllers;

use App\Models\Pratica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use Illuminate\Support\Facades\Log;


class PraticaController extends Controller
{

    public function downloadZip(Request $request, $id)
    {
        $p = Pratica::findOrFail($id);

        if (!$request->has('files')) {
            return back()->with('error', 'Nessun file selezionato.');
        }

        // ⛔ PRIMA ERA: $request->files (sbagliato)
        $selected = $request->input('files', []);  // <-- ARRAY di nomi file (string)

        // 📁 Cartella tmp
        $tmpDir = storage_path("app/tmp");
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $zipName = "pratica_{$p->id}_" . time() . ".zip";
        $zipPath = $tmpDir . "/" . $zipName;

        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Impossibile creare lo ZIP.');
        }

        $baseFolder = storage_path("app/public/PELAGO/PDF/" . $p->cartella);
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

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    public function show($id)
    {
        $p = Pratica::findOrFail($id);

        // Percorso della cartella PDF
        $folder = storage_path("app/public/PELAGO/PDF/" . $p->cartella);

        $pdfFiles = [];
        if (File::exists($folder)) {
            $pdfFiles = collect(File::files($folder))
                ->filter(fn($f) => strtolower($f->getExtension()) === 'pdf')
                ->map(fn($f) => [
                    'name' => $f->getFilename(),
                    'url' => asset("storage/PELAGO/PDF/{$p->cartella}/" . $f->getFilename())
                ])
                ->values()
                ->toArray();
        }

        return view('pratica.show', compact('p', 'pdfFiles'));
    }
}
