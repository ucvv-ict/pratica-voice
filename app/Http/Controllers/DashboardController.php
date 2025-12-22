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

        // Raccogliamo tutti i filtri gestiti (alias compresi)
        $filters = $request->only([
            'q',
            'id',
            'numero_protocollo',
            'numero_pratica',
            'anno',
            'oggetto',
            'richiedente',
            'tipo',
            'via',
            'civico',
            'riferimento_libero',
            'nota',
            'foglio',
            'particella_sub',
            'protocollo_da',
            'protocollo_a',
            'rilascio_da',
            'rilascio_a',
            'created_da',
            'created_a',
            'pratica_id',
            'numero_rilascio',
            'pdf',
            'vuote',
        ]);

        // Compatibilità con vecchi parametri
        $filters['richiedente'] = $filters['richiedente'] ?? $request->input('cognome');
        $filters['anno'] = $filters['anno'] ?? $request->input('anno_presentazione');

        // Ricerca globale
        if (filled($filters['q'] ?? null)) {
            $term = $filters['q'];

            $query->where(function ($s) use ($term) {
                $s->where('numero_protocollo', 'like', "%{$term}%")
                  ->orWhere('numero_pratica', 'like', "%{$term}%")
                  ->orWhere('oggetto', 'like', "%{$term}%")
                  ->orWhere('rich_cognome1', 'like', "%{$term}%")
                  ->orWhere('rich_nome1', 'like', "%{$term}%")
                  ->orWhere('rich_cognome2', 'like', "%{$term}%")
                  ->orWhere('rich_nome2', 'like', "%{$term}%");
            });
        }

        // Filtri a uguaglianza
        $exactFilters = [
            'id' => 'id',
            'numero_protocollo' => 'numero_protocollo',
            'numero_pratica' => 'numero_pratica',
            'anno' => 'anno_presentazione',
            'tipo' => 'sigla_tipo_pratica',
            'foglio' => 'foglio',
            'pratica_id' => 'pratica_id',
            'numero_rilascio' => 'numero_rilascio',
        ];

        foreach ($exactFilters as $input => $column) {
            if (filled($filters[$input] ?? null)) {
                $query->where($column, $filters[$input]);
            }
        }

        // Filtri LIKE
        $likeFilters = [
            'oggetto' => 'oggetto',
            'riferimento_libero' => 'riferimento_libero',
            'nota' => 'nota',
            'via' => 'area_circolazione',
            'civico' => 'civico_esponente',
            'particella_sub' => 'particella_sub',
        ];

        foreach ($likeFilters as $input => $column) {
            if (filled($filters[$input] ?? null)) {
                $query->where($column, 'like', '%' . $filters[$input] . '%');
            }
        }

        // Richiedente: cerca su tutti i campi nome/cognome
        if (filled($filters['richiedente'] ?? null)) {
            $name = $filters['richiedente'];

            $query->where(function ($q) use ($name) {
                $q->where('rich_cognome1', 'LIKE', "%{$name}%")
                  ->orWhere('rich_nome1', 'LIKE', "%{$name}%")
                  ->orWhere('rich_cognome2', 'LIKE', "%{$name}%")
                  ->orWhere('rich_nome2', 'LIKE', "%{$name}%")
                  ->orWhere('rich_cognome3', 'LIKE', "%{$name}%")
                  ->orWhere('rich_nome3', 'LIKE', "%{$name}%");
            });
        }

        // Range di date
        $dateRanges = [
            'data_protocollo' => ['from' => 'protocollo_da', 'to' => 'protocollo_a'],
            'data_rilascio' => ['from' => 'rilascio_da', 'to' => 'rilascio_a'],
            'created_at' => ['from' => 'created_da', 'to' => 'created_a'],
        ];

        foreach ($dateRanges as $column => $range) {
            if (filled($filters[$range['from']] ?? null)) {
                $query->whereDate($column, '>=', $filters[$range['from']]);
            }

            if (filled($filters[$range['to']] ?? null)) {
                $query->whereDate($column, '<=', $filters[$range['to']]);
            }
        }

        /* -------------------------------------------------
         * 📄 Ricerca nei PDF (fase 1: filtriamo gli ID)
         * ------------------------------------------------- */

        $pdfTerm = null;
        $pdfMatches = [];

        if (filled($filters['pdf'] ?? null)) {
            $pdfTerm = $filters['pdf'];

            $pdfMatches = DB::table('pdf_index')
                ->select('pratica_id')
                ->where('content', 'LIKE', "%{$pdfTerm}%")
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
                    ->where('content', 'LIKE', "%{$pdfTerm}%")
                    ->pluck('file')
                    ->toArray();
                return $p;
            });
        }

        $results->transform(function ($p) {
            // numero_pdf è il campo persistito nel DB
            $p->files_count = $p->numero_pdf ?? 0;
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

        // Conteggio filtri attivi (allineato con la UI unificata)
        $activeFilters = collect($filters)
            ->filter(fn ($value) => filled($value))
            ->count();

        return view('dashboard.index', [
            'pratiche' => $pratiche,
            'activeFilters' => $activeFilters,
            'filters' => $filters,
        ]);
    }
}
