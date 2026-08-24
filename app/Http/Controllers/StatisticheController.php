<?php

namespace App\Http\Controllers;

use App\Services\StatistichePdfService;
use App\Support\Tenant;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StatisticheController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->resolveFilters($request);

        return view('statistiche.index', [
            'tenantName' => Tenant::name(),
            'riepilogo' => $this->riepilogo($filters),
            'statistiche' => $this->statistiche($filters),
            'heatmap' => $this->heatmap(),
            'cumulativo' => $this->cumulativo(),
            'manutenzione' => $this->manutenzione($filters),
            'confronto' => $this->confronto($filters),
            'filters' => $filters,
        ]);
    }

    public function csv(Request $request): StreamedResponse
    {
        $filters = $this->resolveFilters($request);
        $statistiche = $this->statistiche($filters);
        $periodColumn = match ($filters['group']) {
            'day' => 'giorno',
            'week' => 'settimana',
            default => 'mese',
        };

        return response()->streamDownload(function () use ($statistiche, $periodColumn): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, [$periodColumn, 'fascicoli_completati', 'pratiche_distinte', 'errori', 'totale_generazioni']);

            foreach ($statistiche as $periodo) {
                fputcsv($output, [
                    $periodo->periodo,
                    $periodo->fascicoli_completati,
                    $periodo->pratiche_distinte,
                    $periodo->errori,
                    $periodo->totale_generazioni,
                ]);
            }

            fclose($output);
        }, 'statistiche-fascicoli.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function pdf(Request $request, StatistichePdfService $pdfService)
    {
        $filters = $this->resolveFilters($request);
        $monthlyFilters = [...$filters, 'group' => 'month'];
        $manutenzione = $this->manutenzione($filters);
        $confronto = $this->confronto($filters);
        $tenantSlug = Str::slug(Tenant::slug() ?: Tenant::name()) ?: 'installazione';
        $periodSlug = $filters['from'] || $filters['to']
            ? ($filters['from']?->format('Y-m-d') ?? 'inizio').'_'.($filters['to']?->format('Y-m-d') ?? 'oggi')
            : 'tutto-periodo';

        $content = $pdfService->generate([
            'tenant' => Tenant::name(),
            'periodo' => $filters['period_label'],
            'generato_il' => now()->format('d/m/Y H:i'),
            'riepilogo' => $this->riepilogo($filters),
            'utilizzo_mensile' => $this->statistiche($monthlyFilters),
            'manutenzione' => $manutenzione,
            'confronto' => $confronto,
            'confronto_mensile' => $confronto['mensili'],
        ]);

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="praticavoice-statistiche-'.$tenantSlug.'-'.$periodSlug.'.pdf"',
            'Content-Length' => (string) strlen($content),
        ]);
    }

    private function resolveFilters(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'group' => ['nullable', 'in:day,week,month'],
            'preset' => ['nullable', 'in:30_days,3_months,6_months,current_year,all,custom'],
        ]);

        $today = CarbonImmutable::today();
        $preset = $validated['preset'] ?? 'all';
        $from = isset($validated['from']) ? CarbonImmutable::parse($validated['from'])->startOfDay() : null;
        $to = isset($validated['to']) ? CarbonImmutable::parse($validated['to'])->endOfDay() : null;

        if ($from || $to) {
            $preset = 'custom';
        } else {
            [$from, $to] = match ($preset) {
                '30_days' => [$today->subDays(29)->startOfDay(), $today->endOfDay()],
                '3_months' => [$today->subMonths(3)->startOfDay(), $today->endOfDay()],
                '6_months' => [$today->subMonths(6)->startOfDay(), $today->endOfDay()],
                'current_year' => [$today->startOfYear(), $today->endOfDay()],
                default => [null, null],
            };
        }

        return [
            'group' => $validated['group'] ?? 'month',
            'preset' => $preset,
            'from' => $from,
            'to' => $to,
            'input_from' => $validated['from'] ?? null,
            'input_to' => $validated['to'] ?? null,
            'period_label' => $this->periodLabel($from, $to),
        ];
    }

    private function riepilogo(array $filters): object
    {
        return $this->applyPeriod(DB::table('fascicoli_generazione'), $filters)
            ->selectRaw("SUM(CASE WHEN stato = 'completed' THEN 1 ELSE 0 END) as fascicoli_completati")
            ->selectRaw("COUNT(DISTINCT CASE WHEN stato = 'completed' THEN pratica_id END) as pratiche_distinte")
            ->selectRaw("SUM(CASE WHEN stato = 'error' THEN 1 ELSE 0 END) as errori")
            ->selectRaw('COUNT(*) as totale_generazioni')
            ->selectRaw("MIN(CASE WHEN stato = 'completed' THEN created_at END) as primo_fascicolo")
            ->selectRaw("MAX(CASE WHEN stato = 'completed' THEN created_at END) as ultimo_fascicolo")
            ->first();
    }

    private function statistiche(array $filters): Collection
    {
        $periodExpression = $this->periodExpression($filters['group']);
        $statistiche = $this->applyPeriod(DB::table('fascicoli_generazione'), $filters)
            ->selectRaw("{$periodExpression} as periodo")
            ->selectRaw("SUM(CASE WHEN stato = 'completed' THEN 1 ELSE 0 END) as fascicoli_completati")
            ->selectRaw("COUNT(DISTINCT CASE WHEN stato = 'completed' THEN pratica_id END) as pratiche_distinte")
            ->selectRaw("SUM(CASE WHEN stato = 'error' THEN 1 ELSE 0 END) as errori")
            ->selectRaw('COUNT(*) as totale_generazioni')
            ->groupByRaw($periodExpression)
            ->orderBy('periodo')
            ->get();

        if ($filters['group'] === 'day' && $filters['from'] && $filters['to']
            && $filters['from']->diffInDays($filters['to']) <= 366) {
            $statistiche = $this->fillMissingDays($statistiche, $filters['from'], $filters['to']);
        }

        return $statistiche->map(function (object $row) use ($filters): object {
            $start = CarbonImmutable::parse($row->periodo);
            $row->label = match ($filters['group']) {
                'day' => $start->format('d/m/Y'),
                'week' => $this->weekLabel($start),
                default => $row->periodo,
            };
            $row->axis_label = match ($filters['group']) {
                'day' => $start->format('d/m'),
                'week' => $this->weekAxisLabel($start),
                default => $row->periodo,
            };
            $row->tooltip_label = match ($filters['group']) {
                'day' => $start->locale('it')->translatedFormat('j F Y'),
                'week' => $this->weekLabel($start),
                default => $start->locale('it')->translatedFormat('F Y'),
            };
            $row->drill_url = $filters['group'] === 'month'
                ? route('statistiche.index', [
                    'group' => 'day',
                    'from' => $start->startOfMonth()->format('Y-m-d'),
                    'to' => $start->endOfMonth()->format('Y-m-d'),
                    'preset' => 'custom',
                ])
                : null;

            return $row;
        });
    }

    private function applyPeriod(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['from'], fn (Builder $query, CarbonImmutable $from) => $query->where('created_at', '>=', $from))
            ->when($filters['to'], fn (Builder $query, CarbonImmutable $to) => $query->where('created_at', '<=', $to));
    }

    private function periodExpression(string $group): string
    {
        $driver = DB::connection()->getDriverName();

        return match ($driver) {
            'sqlite' => match ($group) {
                'day' => "strftime('%Y-%m-%d', created_at)",
                'week' => "date(created_at, '-' || ((cast(strftime('%w', created_at) as integer) + 6) % 7) || ' days')",
                default => "strftime('%Y-%m', created_at)",
            },
            'pgsql' => match ($group) {
                'day' => "to_char(created_at, 'YYYY-MM-DD')",
                'week' => "to_char(date_trunc('week', created_at), 'YYYY-MM-DD')",
                default => "to_char(created_at, 'YYYY-MM')",
            },
            'sqlsrv' => match ($group) {
                'day' => 'CONVERT(varchar(10), created_at, 23)',
                'week' => "CONVERT(varchar(10), DATEADD(day, -(DATEDIFF(day, '19000101', created_at) % 7), CAST(created_at AS date)), 23)",
                default => "FORMAT(created_at, 'yyyy-MM')",
            },
            default => match ($group) {
                'day' => "DATE_FORMAT(created_at, '%Y-%m-%d')",
                'week' => "DATE_FORMAT(DATE_SUB(DATE(created_at), INTERVAL WEEKDAY(created_at) DAY), '%Y-%m-%d')",
                default => "DATE_FORMAT(created_at, '%Y-%m')",
            },
        };
    }

    private function fillMissingDays(Collection $statistiche, CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        $byDay = $statistiche->keyBy('periodo');

        return collect(CarbonPeriod::create($from->startOfDay(), $to->startOfDay()))
            ->map(function ($day) use ($byDay): object {
                $key = $day->format('Y-m-d');

                return $byDay->get($key) ?? (object) [
                    'periodo' => $key,
                    'fascicoli_completati' => 0,
                    'pratiche_distinte' => 0,
                    'errori' => 0,
                    'totale_generazioni' => 0,
                ];
            });
    }

    private function heatmap(): array
    {
        $to = CarbonImmutable::today();
        $from = $to->subYear()->addDay();
        $dayExpression = $this->periodExpression('day');
        $activity = DB::table('fascicoli_generazione')
            ->where('stato', 'completed')
            ->where('created_at', '>=', $from->startOfDay())
            ->where('created_at', '<=', $to->endOfDay())
            ->selectRaw("{$dayExpression} as periodo")
            ->selectRaw('COUNT(*) as fascicoli_completati')
            ->selectRaw('COUNT(DISTINCT pratica_id) as pratiche_distinte')
            ->groupByRaw($dayExpression)
            ->get()
            ->keyBy('periodo');

        $maxCompleted = max(1, (int) $activity->max('fascicoli_completati'));
        $gridFrom = $from->startOfWeek();
        $gridTo = $to->endOfWeek();
        $months = collect();
        $lastMonth = null;

        $days = collect(CarbonPeriod::create($gridFrom, $gridTo))->map(function ($day) use (
            $activity,
            $from,
            $to,
            $gridFrom,
            $maxCompleted,
            $months,
            &$lastMonth
        ): object {
            $date = CarbonImmutable::instance($day);
            $key = $date->format('Y-m-d');
            $inRange = $date->betweenIncluded($from, $to);
            $row = $activity->get($key);
            $completed = $inRange ? (int) ($row->fascicoli_completati ?? 0) : 0;
            $monthKey = $date->format('Y-m');

            if ($inRange && $monthKey !== $lastMonth) {
                $months->push((object) [
                    'label' => $date->locale('it')->translatedFormat('M'),
                    'week' => intdiv($gridFrom->diffInDays($date), 7),
                ]);
                $lastMonth = $monthKey;
            }

            return (object) [
                'date' => $key,
                'label' => $date->format('d/m/Y'),
                'completed' => $completed,
                'pratiche_distinte' => $inRange ? (int) ($row->pratiche_distinte ?? 0) : 0,
                'level' => $completed === 0 ? 0 : max(1, (int) ceil(($completed / $maxCompleted) * 4)),
                'in_range' => $inRange,
                'weekend' => $date->isWeekend(),
            ];
        });

        return [
            'days' => $days,
            'months' => $months,
            'weeks' => intdiv($gridFrom->diffInDays($gridTo), 7) + 1,
            'from' => $from,
            'to' => $to,
        ];
    }

    private function cumulativo(): Collection
    {
        $firstRecord = DB::table('fascicoli_generazione')
            ->where('created_at', '<=', CarbonImmutable::today()->endOfDay())
            ->min('created_at');

        if (! $firstRecord) {
            return collect();
        }

        $from = CarbonImmutable::parse($firstRecord)->startOfMonth();
        $to = CarbonImmutable::today()->startOfMonth();
        $monthExpression = $this->periodExpression('month');
        $counts = DB::table('fascicoli_generazione')
            ->where('stato', 'completed')
            ->where('created_at', '<=', CarbonImmutable::today()->endOfDay())
            ->selectRaw("{$monthExpression} as periodo")
            ->selectRaw('COUNT(*) as completati')
            ->groupByRaw($monthExpression)
            ->pluck('completati', 'periodo');
        $runningTotal = 0;

        return collect(CarbonPeriod::create($from, '1 month', $to))
            ->map(function ($month) use ($counts, &$runningTotal): object {
                $key = $month->format('Y-m');
                $runningTotal += (int) ($counts[$key] ?? 0);

                return (object) [
                    'periodo' => $key,
                    'label' => $month->locale('it')->translatedFormat('M Y'),
                    'totale' => $runningTotal,
                ];
            });
    }

    private function manutenzione(array $filters): array
    {
        if (! Schema::hasTable('deploy_history')) {
            return [
                'available' => false,
                'has_data' => false,
                'mensili' => collect(),
            ];
        }

        $automaticDeploys = fn () => DB::table('deploy_history')
            ->where('notes', 'deploy.sh')
            ->whereNotNull('created_at');
        $periodQuery = $this->applyPeriod($automaticDeploys(), $filters);
        $today = CarbonImmutable::today();
        $dayExpression = $this->deployDayExpression();
        $monthExpression = $this->deployMonthExpression();
        $commitColumn = DB::connection()->getQueryGrammar()->wrap('commit');

        $ultimoDeploy = (clone $periodQuery)
            ->select('created_at', 'commit')
            ->orderByDesc('created_at')
            ->first();

        if ($ultimoDeploy?->commit) {
            $ultimoDeploy->commit = mb_substr($ultimoDeploy->commit, 0, 12);
        }

        $giorniUltimi90 = (clone $periodQuery)
            ->where('created_at', '>=', $today->subDays(89)->startOfDay())
            ->where('created_at', '<=', $today->endOfDay())
            ->selectRaw("COUNT(DISTINCT {$dayExpression}) as totale")
            ->first();

        $mensili = (clone $periodQuery)
            ->selectRaw("{$monthExpression} as mese")
            ->selectRaw('COUNT(*) as registrazioni_deploy')
            ->selectRaw("COUNT(DISTINCT CASE WHEN {$commitColumn} IS NOT NULL AND {$commitColumn} <> '' THEN {$commitColumn} END) as commit_distinti")
            ->selectRaw("COUNT(DISTINCT {$dayExpression}) as giorni_con_deploy")
            ->groupByRaw($monthExpression)
            ->orderBy('mese')
            ->get();

        return [
            'available' => true,
            'has_data' => $mensili->isNotEmpty(),
            'ultimo_deploy' => $ultimoDeploy,
            'ultimi_30_giorni' => (clone $periodQuery)
                ->where('created_at', '>=', $today->subDays(29)->startOfDay())
                ->where('created_at', '<=', $today->endOfDay())
                ->count(),
            'giorni_ultimi_90' => (int) ($giorniUltimi90->totale ?? 0),
            'commit_ultimi_90' => (clone $periodQuery)
                ->where('created_at', '>=', $today->subDays(89)->startOfDay())
                ->where('created_at', '<=', $today->endOfDay())
                ->whereNotNull('commit')
                ->where('commit', '<>', '')
                ->distinct()
                ->count('commit'),
            'commit_storici' => $automaticDeploys()
                ->whereNotNull('commit')
                ->where('commit', '<>', '')
                ->distinct()
                ->count('commit'),
            'mensili' => $mensili,
        ];
    }

    private function confronto(array $filters): array
    {
        $monthExpression = $this->periodExpression('month');
        $utilizzo = $this->applyPeriod(DB::table('fascicoli_generazione'), $filters)
            ->where('stato', 'completed')
            ->selectRaw("{$monthExpression} as mese")
            ->selectRaw('COUNT(*) as fascicoli_completati')
            ->groupByRaw($monthExpression)
            ->pluck('fascicoli_completati', 'mese');

        $deployAvailable = Schema::hasTable('deploy_history');
        $deploy = collect();
        $commitDistintiPeriodo = 0;

        if ($deployAvailable) {
            $deployMonthExpression = $this->deployMonthExpression();
            $deployDayExpression = $this->deployDayExpression();
            $commitColumn = DB::connection()->getQueryGrammar()->wrap('commit');
            $deployQuery = $this->applyPeriod(
                DB::table('deploy_history')->where('notes', 'deploy.sh')->whereNotNull('created_at'),
                $filters
            );

            $deploy = (clone $deployQuery)
                ->selectRaw("{$deployMonthExpression} as mese")
                ->selectRaw("COUNT(DISTINCT {$deployDayExpression}) as giorni_con_deploy")
                ->selectRaw("COUNT(DISTINCT CASE WHEN {$commitColumn} IS NOT NULL AND {$commitColumn} <> '' THEN {$commitColumn} END) as commit_distinti")
                ->groupByRaw($deployMonthExpression)
                ->get()
                ->keyBy('mese');

            $commitDistintiPeriodo = (clone $deployQuery)
                ->whereNotNull('commit')
                ->where('commit', '<>', '')
                ->distinct()
                ->count('commit');
        }

        $monthKeys = $utilizzo->keys()->merge($deploy->keys())->unique()->sort()->values();
        $rangeFrom = $filters['from']?->startOfMonth()
            ?? ($monthKeys->isNotEmpty() ? CarbonImmutable::parse($monthKeys->first())->startOfMonth() : null);
        $rangeTo = $filters['to']?->startOfMonth()
            ?? ($monthKeys->isNotEmpty() ? CarbonImmutable::parse($monthKeys->last())->startOfMonth() : null);

        if ($rangeFrom && ! $rangeTo) {
            $currentMonth = CarbonImmutable::today()->startOfMonth();
            $rangeTo = $rangeFrom->greaterThan($currentMonth) ? $rangeFrom : $currentMonth;
        } elseif ($rangeTo && ! $rangeFrom) {
            $rangeFrom = $rangeTo;
        }

        $mensili = collect();
        if ($rangeFrom && $rangeTo && $rangeFrom->lessThanOrEqualTo($rangeTo)) {
            $mensili = collect(CarbonPeriod::create($rangeFrom, '1 month', $rangeTo))
                ->map(function ($month) use ($utilizzo, $deploy): object {
                    $key = $month->format('Y-m');
                    $deployRow = $deploy->get($key);

                    return (object) [
                        'mese' => $key,
                        'label' => $month->locale('it')->translatedFormat('M Y'),
                        'fascicoli_completati' => (int) ($utilizzo[$key] ?? 0),
                        'giorni_con_deploy' => (int) ($deployRow->giorni_con_deploy ?? 0),
                        'commit_distinti' => (int) ($deployRow->commit_distinti ?? 0),
                    ];
                });
        }

        $totaleFascicoli = (int) $mensili->sum('fascicoli_completati');
        $totaleGiorniDeploy = (int) $mensili->sum('giorni_con_deploy');

        return [
            'deploy_available' => $deployAvailable,
            'mensili' => $mensili,
            'fascicoli' => $totaleFascicoli,
            'giorni_deploy' => $totaleGiorniDeploy,
            'commit_distinti' => $commitDistintiPeriodo,
            'fascicoli_per_giorno_deploy' => $totaleGiorniDeploy > 0 ? round($totaleFascicoli / $totaleGiorniDeploy, 1) : null,
            'fascicoli_per_commit' => $commitDistintiPeriodo > 0 ? round($totaleFascicoli / $commitDistintiPeriodo, 1) : null,
        ];
    }

    private function deployMonthExpression(): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', created_at)",
            'pgsql' => "to_char(created_at, 'YYYY-MM')",
            'sqlsrv' => "FORMAT(created_at, 'yyyy-MM')",
            default => "DATE_FORMAT(created_at, '%Y-%m')",
        };
    }

    private function deployDayExpression(): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m-%d', created_at)",
            'pgsql' => "to_char(created_at, 'YYYY-MM-DD')",
            'sqlsrv' => 'CONVERT(varchar(10), created_at, 23)',
            default => "DATE_FORMAT(created_at, '%Y-%m-%d')",
        };
    }

    private function periodLabel(?CarbonImmutable $from, ?CarbonImmutable $to): string
    {
        if (! $from && ! $to) {
            return 'Tutto il periodo disponibile';
        }

        $format = fn (CarbonImmutable $date) => $date->locale('it')->translatedFormat('j F Y');

        if ($from && $to) {
            return $format($from).' – '.$format($to);
        }

        return $from ? 'Dal '.$format($from) : 'Fino al '.$format($to);
    }

    private function weekLabel(CarbonImmutable $start): string
    {
        $end = $start->addDays(6);

        if ($start->month === $end->month) {
            return $start->day.'–'.$end->locale('it')->translatedFormat('j M Y');
        }

        return $start->locale('it')->translatedFormat('j M').' – '.$end->locale('it')->translatedFormat('j M Y');
    }

    private function weekAxisLabel(CarbonImmutable $start): string
    {
        $end = $start->addDays(6);

        if ($start->month === $end->month) {
            return $start->day.'–'.$end->locale('it')->translatedFormat('j M');
        }

        return $start->locale('it')->translatedFormat('j M').'–'.$end->locale('it')->translatedFormat('j M');
    }
}
