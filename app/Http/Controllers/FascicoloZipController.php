<?php

namespace App\Http\Controllers;

use App\Models\FascicoloGenerazione as FascicoloZip;

class FascicoloZipController extends Controller
{
    public function status(int $id)
    {
        $fascicolo = FascicoloZip::findOrFail($id);

        return response()->json([
            'stato'    => $fascicolo->stato,
            'progress' => $fascicolo->progress,
            'file_zip' => $fascicolo->file_zip,
        ]);
    }

    public function download(int $id)
    {
        $fascicolo = FascicoloZip::findOrFail($id);

        abort_unless($fascicolo->stato === 'completed', 404);

        return response()->download($fascicolo->file_zip);
    }
}
