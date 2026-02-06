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

        // 🔍 Tutti i filtri gestiti (UI + alias)
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
            'cartella',
            'pratica_id',
            'numero_rilascio',
            'pdf',
            'vuote',
            'exact',
            'gruppo_pratica', // ✅ NUOVO
        ]);

        // Compatibilità parametri legacy
        $filters['richiedente'] = $filters['richiedente'] ?? $request->input('cognome');
        $filters['anno'] = $filters['anno'] ?? $request->input('anno_presentazione');
        $filters['cartella'] = $filters['cartella'] ?? $filters['pratica_id'] ?? null;

        $exactMode = $request->boolean('exact');

        /* -------------------------------------------------
         * 🔎 Ricerca globale
         * ------------------------------------------------- */
        if (filled($filters['q'] ?? null)) {
            $term = $filters['q'];

            $metadataFields = [
                'numero_protocollo',
                'numero_pratica',
                'numero_rilascio',
                'oggetto',
                'pratica_id',
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

            $query->where(function ($s) use ($term, $metadataFields) {
                $s->where('numero_protocollo', 'like', "%{$term}%")
                  ->orWhere('numero_pratica', 'like', "%{$term}%")
                  ->orWhere('numero_rilascio', 'like', "%{$term}%")
                  ->orWhere('pratica_id', 'like', "%{$term}%")
                  ->orWhere('oggetto', 'like', "%{$term}%")
                  ->orWhere('rich_cognome1', 'like', "%{$term}%")
                  ->orWhere('rich_nome1', 'like', "%{$term}%")
                  ->orWhere('rich_cognome2', 'like', "%{$term}%")
                  ->orWhere('rich_nome2', 'like', "%{$term}%")
                  ->orWhere('rich_cognome3', 'like', "%{$term}%")
                  ->orWhere('rich_nome3', 'like', "%{$term}%")
                  ->orWhereExists(function ($q) use ($term, $metadataFields) {
                      $q->select(DB::raw(1))
                        ->from('metadati_aggiornati as ma')
                        ->whereColumn('ma.pratica_id', 'pratiche.id')
                        ->where(function ($inner) use ($metadataFields, $term) {
                            foreach ($metadataFields as $field) {
                                $inner->orWhereRaw(
                                    "JSON_UNQUOTE(JSON_EXTRACT(ma.dati, '$.\"{$field}\"')) LIKE ?",
                                    ["%{$term}%"]
                                );
                            }
                        });
                  });
            });
        }

        /* -------------------------------------------------
         * 🎯 Filtro: GRUPPO PRATICA (fascicolo logico)
         * ------------------------------------------------- */
        if (
            array_key_exists('gruppo_pratica', $filters)
            && $filters['gruppo_pratica'] !== null
            && $filters['gruppo_pratica'] !== ''
        ) {
            $query->where('gruppo_bat', $filters['gruppo_pratica']);
        }

        /* -------------------------------------------------
         * 🎯 Filtri a uguaglianza / LIKE controllato
         * ------------------------------------------------- */
        $exactFilters = [
            'id' => 'id',
            'numero_protocollo' => 'numero_protocollo',
            'numero_pratica' => 'numero_pratica',
            'anno' => 'anno_presentazione',
            'tipo' => 'sigla_tipo_pratica',
            'foglio' => 'foglio',
            'cartella' => 'pratica_id',
            'numero_rilascio' => 'numero_rilascio',
        ];

        foreach ($exactFilters as $input => $column) {
            if (filled($filters[$input] ?? null)) {
                $value = $filters[$input];
                $alwaysExact = in_array($column, ['id', 'pratica_id'], true);
                $useLike = !$exactMode && !$alwaysExact;

                $query->where(function ($q) use ($column, $value, $useLike) {
                    if ($useLike) {
                        $likeValue = '%' . $value . '%';
                        $q->where($column, 'like', $likeValue)
                          ->orWhereExists(function ($sub) use ($column, $likeValue) {
                              $sub->select(DB::raw(1))
                                  ->from('metadati_aggiornati as ma')
                                  ->whereColumn('ma.pratica_id', 'pratiche.id')
                                  ->whereRaw(
                                      "JSON_UNQUOTE(JSON_EXTRACT(ma.dati, '$.\"{$column}\"')) LIKE ?",
                                      [$likeValue]
                                  );
                          });
                    } else {
                        $q->where($column, $value)
                          ->orWhereExists(function ($sub) use ($column, $value) {
                              $sub->select(DB::raw(1))
                                  ->from('metadati_aggiornati as ma')
                                  ->whereColumn('ma.pratica_id', 'pratiche.id')
                                  ->whereRaw(
                                      "JSON_UNQUOTE(JSON_EXTRACT(ma.dati, '$.\"{$column}\"')) = ?",
                                      [$value]
                                  );
                          });
                    }
                });
            }
        }

        /* -------------------------------------------------
         * 🔤 Filtri LIKE semplici
         * ------------------------------------------------- */
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
                $query->where(function ($q) use ($column, $value) {
                    $q->where($column, 'like', $value)
                      ->orWhereExists(function ($sub) use ($column, $value) {
                          $sub->select(DB::raw(1))
                              ->from('metadati_aggiornati as ma')
                              ->whereColumn('ma.pratica_id', 'pratiche.id')
                              ->whereRaw(
                                  "JSON_UNQUOTE(JSON_EXTRACT(ma.dati, '$.\"{$column}\"')) LIKE ?",
                                  [$value]
                              );
                      });
                });
            }
        }

        /* -------------------------------------------------
         * 👤 Richiedente
         * ------------------------------------------------- */
        if (filled($filters['richiedente'] ?? null)) {
            $name = $filters['richiedente'];

            $query->where(function ($q) use ($name) {
                foreach ([
                    'rich_cognome1', 'rich_nome1',
                    'rich_cognome2', 'rich_nome2',
                    'rich_cognome3', 'rich_nome3',
                ] as $field) {
                    $q->orWhere($field, 'LIKE', "%{$name}%");
                }

                $q->orWhereExists(function ($sub) use ($name) {
                    $sub->select(DB::raw(1))
                        ->from('metadati_aggiornati as ma')
                        ->whereColumn('ma.pratica_id', 'pratiche.id')
                        ->where(function ($inner) use ($name) {
                            foreach ([
                                'rich_cognome1', 'rich_nome1',
                                'rich_cognome2', 'rich_nome2',
                                'rich_cognome3', 'rich_nome3',
                            ] as $field) {
                                $inner->orWhereRaw(
                                    "JSON_UNQUOTE(JSON_EXTRACT(ma.dati, '$.\"{$field}\"')) LIKE ?",
                                    ["%{$name}%"]
                                );
                            }
                        });
                });
            });
        }

        /* -------------------------------------------------
         * 📅 Range date
         * ------------------------------------------------- */
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
         * 📄 Ricerca PDF
         * ------------------------------------------------- */
        $pdfTerm = null;

        if (filled($filters['pdf'] ?? null)) {
            $pdfTerm = $filters['pdf'];

            $pdfMatches = DB::table('pdf_index')
                ->where('content', 'LIKE', "%{$pdfTerm}%")
                ->pluck('pratica_id')
                ->unique()
                ->toArray();

            if ($pdfMatches) {
                $query->whereIn('id', $pdfMatches);
            } else {
                $query->whereRaw('0 = 1');
            }
        }

        /* -------------------------------------------------
         * 📥 Esecuzione query
         * ------------------------------------------------- */
        $results = $query->with('ultimoMetadata')->get();

        if ($pdfTerm) {
            $results->transform(function ($p) use ($pdfTerm) {
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
                $cognome = $p->resolved['rich_cognome' . $i] ?? $p->{'rich_cognome' . $i};
                $nome    = $p->resolved['rich_nome' . $i] ?? $p->{'rich_nome' . $i};
                if ($cognome || $nome) {
                    $resolvedRichiedenti[] = trim("$cognome $nome");
                }
            }
            $p->richiedenti_resolti = implode(', ', $resolvedRichiedenti);

            return $p;
        });

        /* -------------------------------------------------
         * ↕ Ordinamento
         * ------------------------------------------------- */
        $sort = $request->input('sort', 'pratica_id');
        $dir  = $request->input('dir', 'desc');

        $results = $results->sortBy(fn ($p) =>
            $p->resolved[$sort] ?? $p->{$sort} ?? null,
            SORT_REGULAR,
            $dir === 'desc'
        );

        /* -------------------------------------------------
         * 🚫 Solo pratiche senza file
         * ------------------------------------------------- */
        if ($request->filled('vuote') && $request->vuote == '1') {
            $results = $results->filter(fn ($p) => $p->files_count === 0);
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

        $activeFilters = collect($filters)
            ->filter(fn ($v) => !is_null($v) && $v !== '')
            ->count();

        return view('dashboard.index', [
            'pratiche' => $pratiche,
            'activeFilters' => $activeFilters,
            'filters' => $filters,
        ]);
    }
}
