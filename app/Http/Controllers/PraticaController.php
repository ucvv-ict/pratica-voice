<?php

namespace App\Http\Controllers;

use App\Models\Pratica;
use Illuminate\Support\Facades\File;

class PraticaController extends Controller
{
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
