<?php

namespace App\Http\Controllers;

use App\Models\Pratica;
use App\Models\AccessoAtti;
use App\Models\FascicoloGenerazione;
use App\Models\FascicoloGenerazione as FascicoloZip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use App\Jobs\GeneraFascicoloJob;
use App\Support\Tenant;

class PraticaController extends Controller
{

    public function downloadZip(Request $request, $id)
    {
        $pratica = Pratica::findOrFail($id);

        if (!$request->has('files')) {
            return back()->with('error', 'Nessun file selezionato.');
        }

        $selected = $request->input('files', []);  // array nomi file
        if (empty($selected)) {
            return back()->with('error', 'Nessun file selezionato.');
        }
        $versione = (FascicoloGenerazione::where('pratica_id', $pratica->id)->max('versione') ?? 0) + 1;

        $fascicolo = FascicoloGenerazione::create([
            'pratica_id' => $pratica->id,
            'versione'   => $versione,
            'stato'      => 'pending',
            'progress'   => 0,
            'files_selezionati' => $selected,
        ]);

        GeneraFascicoloJob::dispatch($fascicolo->id);

        return back()->with('success', 'Fascicolo in coda. Aggiorna la pagina per vedere lo stato.');
    }


    public function show($id)
    {
        // 📌 Carica pratica
        $pratica = Pratica::findOrFail($id);

        // 📁 Percorso cartella PDF della pratica
        $folder = Tenant::praticaPdfFolder($pratica->cartella);

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

        $fascicoli = FascicoloGenerazione::where('pratica_id', $pratica->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $fascicoloInCorso = FascicoloZip::where('pratica_id', $pratica->id)
            ->whereIn('stato', ['pending', 'running'])
            ->orderByDesc('versione')
            ->first();

        $fascicoloCompletato = FascicoloZip::where('pratica_id', $pratica->id)
            ->where('stato', 'completed')
            ->orderByDesc('versione')
            ->first();

        $fascicoloZip = $fascicoloInCorso ?? $fascicoloCompletato;

        // 🔥 Passiamo tutto alla view
        return view('pratica.show', [
            'pratica'  => $pratica,
            'pdfFiles' => $pdfFiles,
            'accessi'  => $accessi,
            'fascicoli' => $fascicoli,
            'fascicoloZip' => $fascicoloZip,
        ]);
    }

    private function buildPdfUrl(string $cartella, string $file): string
    {
        return route('pdf.serve', [
            'cartella' => $cartella,
            'file'     => $file,
        ]);
    }

    public function fascicoloStatus($praticaId, $fascicoloId)
    {
        $fascicolo = FascicoloGenerazione::where('pratica_id', $praticaId)
            ->findOrFail($fascicoloId);

        return response()->json([
            'stato'     => $fascicolo->stato,
            'progress'  => $fascicolo->progress,
            'errore'    => $fascicolo->errore,
            'download'  => ($fascicolo->stato === 'completed' && $fascicolo->file_zip)
                ? route('pratica.fascicolo.download', [$praticaId, $fascicoloId])
                : null,
        ]);
    }

    public function downloadFascicolo($praticaId, $fascicoloId)
    {
        $fascicolo = FascicoloGenerazione::where('pratica_id', $praticaId)
            ->findOrFail($fascicoloId);

        if ($fascicolo->stato !== 'completed' || !$fascicolo->file_zip) {
            abort(404);
        }

        $zipPath = $fascicolo->file_zip;

        if (!file_exists($zipPath)) {
            abort(404);
        }

        $filename = basename($zipPath);

        return response()->streamDownload(function () use ($zipPath, $fascicolo) {
            $stream = fopen($zipPath, 'rb');
            while (!feof($stream)) {
                echo fread($stream, 1024 * 256);
            }
            fclose($stream);

            if (file_exists($zipPath)) {
                unlink($zipPath);
            }

            $fascicolo->update(['file_zip' => null]);
        }, $filename, [
            'Content-Type' => 'application/zip',
        ]);
    }
}
