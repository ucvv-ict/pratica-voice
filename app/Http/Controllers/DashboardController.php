<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pratica;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $query = Pratica::query();

        /* -------------------------------------------------
         * 🔍 Ricerca full-text (multi campo)
         * ------------------------------------------------- */
        if ($request->filled('q')) {
            $q = $request->q;

            $query->where(function($s) use ($q) {
                $s->where('oggetto', 'like', "%$q%")
                  ->orWhere('rich_cognome1', 'like', "%$q%")
                  ->orWhere('rich_nome1', 'like', "%$q%")
                  ->orWhere('numero_pratica', 'like', "%$q%")
                  ->orWhere('area_circolazione', 'like', "%$q%")
                  ->orWhere('civico_esponente', 'like', "%$q%");
            });
        }


        /* -------------------------------------------------
         * 🎚 Filtri standard
         * ------------------------------------------------- */

        if ($request->filled('anno')) {
            $query->where('anno_presentazione', $request->anno);
        }

        if ($request->filled('cognome')) {
            $c = $request->cognome;

            $query->where(function($q) use ($c) {
                $q->where('rich_cognome1', 'LIKE', "%$c%")
                  ->orWhere('rich_nome1', 'LIKE', "%$c%")
                  ->orWhere('rich_cognome2', 'LIKE', "%$c%")
                  ->orWhere('rich_nome2', 'LIKE', "%$c%")
                  ->orWhere('rich_cognome3', 'LIKE', "%$c%")
                  ->orWhere('rich_nome3', 'LIKE', "%$c%");
            });
        }

        if ($request->filled('tipo')) {
            $query->where('sigla_tipo_pratica', $request->tipo);
        }

        if ($request->filled('via')) {
            $query->where('area_circolazione', 'like', "%".$request->via."%");
        }

        if ($request->filled('numero_pratica')) {
            $query->where('numero_pratica', $request->numero_pratica);
        }


        /* -------------------------------------------------
         * 📅 Filtri avanzati
         * ------------------------------------------------- */

        if ($request->filled('protocollo_da')) {
            $query->where('data_protocollo', '>=', $request->protocollo_da);
        }

        if ($request->filled('protocollo_a')) {
            $query->where('data_protocollo', '<=', $request->protocollo_a);
        }

        if ($request->filled('rilascio_da')) {
            $query->where('data_rilascio', '>=', $request->rilascio_da);
        }

        if ($request->filled('rilascio_a')) {
            $query->where('data_rilascio', '<=', $request->rilascio_a);
        }

        if ($request->filled('foglio')) {
            $query->where('foglio', $request->foglio);
        }

        if ($request->filled('particella_sub')) {
            $query->where('particella_sub', 'like', "%".$request->particella_sub."%");
        }

        if ($request->filled('nota')) {
            $query->where('nota', 'like', "%".$request->nota."%");
        }

        if ($request->filled('riferimento_libero')) {
            $query->where('riferimento_libero', 'like', "%".$request->riferimento_libero."%");
        }



        /* -------------------------------------------------
         * 📄 Ricerca nei PDF (fase 1: filtriamo gli ID)
         * ------------------------------------------------- */

        $pdfTerm = null;
        $pdfMatches = [];

        if ($request->filled('pdf')) {
            $pdfTerm = $request->pdf;

            $pdfMatches = DB::table('pdf_index')
                ->select('pratica_id')
                ->where('content', 'LIKE', "%$pdfTerm%")
                ->distinct()
                ->pluck('pratica_id')
                ->toArray();

            if (!empty($pdfMatches)) {
                $query->whereIn('id', $pdfMatches);
            } else {
                // evita errori: nessun risultato nei PDF → nessuna pratica
                $query->whereRaw('0 = 1');
            }
        }


        /* -------------------------------------------------
         * 📥 Otteniamo tutte le pratiche filtrate dal DB
         * ------------------------------------------------- */
        $results = $query->get();


        /* -------------------------------------------------
         * 📄 (fase 2) Per ogni pratica → quali PDF contengono il termine?
         * ------------------------------------------------- */
        if ($pdfTerm) {
            $results->transform(function($p) use ($pdfTerm) {
                $p->pdf_hits = DB::table('pdf_index')
                    ->where('pratica_id', $p->id)
                    ->where('content', 'LIKE', "%$pdfTerm%")
                    ->pluck('file')
                    ->toArray();
                return $p;
            });
        }


        /* -------------------------------------------------
         * 📎 Conteggio PDF presenti nella cartella
         * ------------------------------------------------- */
        $results->transform(function ($p) {
            $folder = storage_path("app/public/PELAGO/PDF/" . $p->cartella);

            if (!is_dir($folder)) {
                $p->files_count = 0;
                return $p;
            }

            $files = array_filter(scandir($folder), function ($f) {
                return !in_array($f, ['.', '..']) && str_ends_with(strtolower($f), '.pdf');
            });

            $p->files_count = count($files);
            return $p;
        });


        /* -------------------------------------------------
         * ↕ Ordinamento lato PHP
         * ------------------------------------------------- */
        $sort = $request->input('sort', 'id');
        $dir  = $request->input('dir', 'desc');

        $results = $results->sortBy($sort, SORT_REGULAR, $dir === 'desc');


        /* -------------------------------------------------
         * 🚫 Filtro: solo pratiche senza file (0 documenti)
         * ------------------------------------------------- */
        if ($request->filled('vuote') && $request->vuote == '1') {
            $results = $results->filter(fn($p) => $p->files_count === 0);
        }


        /* -------------------------------------------------
         * 📄 Paginazione manuale
         * ------------------------------------------------- */
        $page    = $request->input('page', 1);
        $perPage = 25;

        $pratiche = new \Illuminate\Pagination\LengthAwarePaginator(
            $results->slice(($page - 1) * $perPage, $perPage)->values(),
            $results->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('dashboard.index', compact('pratiche'));
    }
}
