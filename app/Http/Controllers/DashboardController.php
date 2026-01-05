<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pratica;
use Illuminate\Support\Facades\DB;
use App\Services\MetadataResolver;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $query = Pratica::query();
        $resolver = app(MetadataResolver::class);

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
            'exact',
        ]);

        // Compatibilità con vecchi parametri
        $filters['richiedente'] = $filters['richiedente'] ?? $request->input('cognome');
        $filters['anno'] = $filters['anno'] ?? $request->input('anno_presentazione');
        $exactMode = $request->boolean('exact');

        // Ricerca globale
        if (filled($filters['q'] ?? null)) {
            $term = $filters['q'];
            $metadataFields = [
                'numero_protocollo',
                'numero_pratica',
                'oggetto',
                'rich_cognome1', 'rich_nome1',
                'rich_cognome2', 'rich_nome2',
                'rich_cognome3', 'rich_nome3',
                'area_circolazione',
                'civico_esponente',
                'riferimento_libero',
                'anno_presentazione',
                'nota',
                'particella_sub',
                'foglio',
            ];

            $query->where(function ($s) use ($term) {
                $s->where('numero_protocollo', 'like', "%{$term}%")
                  ->orWhere('numero_pratica', 'like', "%{$term}%")
                  ->orWhere('oggetto', 'like', "%{$term}%")
                  ->orWhere('rich_cognome1', 'like', "%{$term}%")
                  ->orWhere('rich_nome1', 'like', "%{$term}%")
                  ->orWhere('rich_cognome2', 'like', "%{$term}%")
                  ->orWhere('rich_nome2', 'like', "%{$term}%")
                  ->orWhereExists(function($q) use ($term, $metadataFields) {
                      $q->select(DB::raw(1))
                        ->from('metadati_aggiornati as ma')
                        ->whereColumn('ma.pratica_id', 'pratiche.id')
                        ->where(function($inner) use ($metadataFields, $term) {
                            foreach ($metadataFields as $field) {
                                $inner->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(ma.dati, '$.\"{$field}\"')) LIKE ?", ["%{$term}%"]);
                            }
                        });
                  });
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
                $value = $filters[$input];
                $alwaysExact = in_array($column, ['id', 'pratica_id'], true);
                $useLike = !$exactMode && !$alwaysExact;

                $query->where(function($q) use ($column, $value, $useLike) {
                    if ($useLike) {
                        $likeValue = '%' . $value . '%';
                        $q->where($column, 'like', $likeValue)
                          ->orWhereExists(function($sub) use ($column, $likeValue) {
                              $sub->select(DB::raw(1))
                                  ->from('metadati_aggiornati as ma')
                                  ->whereColumn('ma.pratica_id', 'pratiche.id')
                                  ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(ma.dati, '$.\"{$column}\"')) LIKE ?", [$likeValue]);
                          });
                    } else {
                        $q->where($column, $value)
                          ->orWhereExists(function($sub) use ($column, $value) {
                              $sub->select(DB::raw(1))
                                  ->from('metadati_aggiornati as ma')
                                  ->whereColumn('ma.pratica_id', 'pratiche.id')
                                  ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(ma.dati, '$.\"{$column}\"')) = ?", [$value]);
                          });
                    }
                });
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
                $value = '%' . $filters[$input] . '%';
                $query->where(function($q) use ($column, $value) {
                    $q->where($column, 'like', $value)
                      ->orWhereExists(function($sub) use ($column, $value) {
                          $sub->select(DB::raw(1))
                              ->from('metadati_aggiornati as ma')
                              ->whereColumn('ma.pratica_id', 'pratiche.id')
                              ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(ma.dati, '$.\"{$column}\"')) LIKE ?", [$value]);
                      });
                });
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
                  ->orWhere('rich_nome3', 'LIKE', "%{$name}%")
                  ->orWhereExists(function($sub) use ($name) {
                      $sub->select(DB::raw(1))
                          ->from('metadati_aggiornati as ma')
                          ->whereColumn('ma.pratica_id', 'pratiche.id')
                          ->where(function($inner) use ($name) {
                              foreach (['rich_cognome1', 'rich_nome1', 'rich_cognome2', 'rich_nome2', 'rich_cognome3', 'rich_nome3'] as $field) {
                                  $inner->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(ma.dati, '$.\"{$field}\"')) LIKE ?", ["%{$name}%"]);
                              }
                          });
                  });
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
         * 📥 Otteniamo tutte le pratiche filtrate dal DB (con ultimo metadata)
         * ------------------------------------------------- */
        $results = $query->with('ultimoMetadata')->get();


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

        $results->transform(function ($p) use ($resolver) {
            $p->files_count = $p->numero_pdf ?? 0;
            $p->original_values = $p->getAttributes();

            $p->resolved_metadata = $p->ultimoMetadata && is_array($p->ultimoMetadata->dati)
                ? $p->ultimoMetadata->dati
                : [];

            $p->resolved = $resolver->resolve($p);
            $p->metadata_diff = $resolver->diff($p);
            $resolvedRichiedenti = [];
            for ($i = 1; $i <= 3; $i++) {
                $cognome = $p->resolved['rich_cognome' . $i] ?? ($p->{'rich_cognome' . $i} ?? null);
                $nome    = $p->resolved['rich_nome' . $i] ?? ($p->{'rich_nome' . $i} ?? null);
                if ($cognome || $nome) {
                    $resolvedRichiedenti[] = trim(($cognome ?? '') . ' ' . ($nome ?? ''));
                }
            }
            $p->richiedenti_resolti = implode(', ', $resolvedRichiedenti);

            return $p;
        });


        /* -------------------------------------------------
         * ↕ Ordinamento lato PHP
         * ------------------------------------------------- */
        $sort = $request->input('sort', 'id');
        $dir  = $request->input('dir', 'desc');

        $results = $results->sortBy(function($p) use ($sort) {
            return $p->resolved[$sort] ?? $p->{$sort} ?? null;
        }, SORT_REGULAR, $dir === 'desc');


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
