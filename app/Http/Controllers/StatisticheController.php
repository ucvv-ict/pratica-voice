<?php

namespace App\Http\Controllers;

use App\Support\Tenant;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
}
