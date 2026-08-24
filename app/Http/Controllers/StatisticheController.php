<?php

namespace App\Http\Controllers;

use App\Support\Tenant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StatisticheController extends Controller
{
    public function index()
    {
        $riepilogo = DB::table('fascicoli_generazione')
            ->selectRaw("SUM(CASE WHEN stato = 'completed' THEN 1 ELSE 0 END) as fascicoli_completati")
            ->selectRaw("COUNT(DISTINCT CASE WHEN stato = 'completed' THEN pratica_id END) as pratiche_distinte")
            ->selectRaw("SUM(CASE WHEN stato = 'error' THEN 1 ELSE 0 END) as errori")
            ->selectRaw('COUNT(*) as totale_generazioni')
            ->selectRaw("MIN(CASE WHEN stato = 'completed' THEN created_at END) as primo_fascicolo")
            ->selectRaw("MAX(CASE WHEN stato = 'completed' THEN created_at END) as ultimo_fascicolo")
            ->first();

        return view('statistiche.index', [
            'tenantName' => Tenant::name(),
            'riepilogo' => $riepilogo,
            'mensili' => $this->statisticheMensili(),
        ]);
    }

    public function csv(): StreamedResponse
    {
        $mensili = $this->statisticheMensili();

        return response()->streamDownload(function () use ($mensili): void {
            $output = fopen('php://output', 'w');

            fputcsv($output, [
                'mese',
                'fascicoli_completati',
                'pratiche_distinte',
                'errori',
                'totale_generazioni',
            ]);

            foreach ($mensili as $mese) {
                fputcsv($output, [
                    $mese->mese,
                    $mese->fascicoli_completati,
                    $mese->pratiche_distinte,
                    $mese->errori,
                    $mese->totale_generazioni,
                ]);
            }

            fclose($output);
        }, 'statistiche-fascicoli.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function statisticheMensili(): Collection
    {
        $monthExpression = match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', created_at)",
            'pgsql' => "to_char(created_at, 'YYYY-MM')",
            'sqlsrv' => "FORMAT(created_at, 'yyyy-MM')",
            default => "DATE_FORMAT(created_at, '%Y-%m')",
        };

        return DB::table('fascicoli_generazione')
            ->selectRaw("{$monthExpression} as mese")
            ->selectRaw("SUM(CASE WHEN stato = 'completed' THEN 1 ELSE 0 END) as fascicoli_completati")
            ->selectRaw("COUNT(DISTINCT CASE WHEN stato = 'completed' THEN pratica_id END) as pratiche_distinte")
            ->selectRaw("SUM(CASE WHEN stato = 'error' THEN 1 ELSE 0 END) as errori")
            ->selectRaw('COUNT(*) as totale_generazioni')
            ->groupByRaw($monthExpression)
            ->orderBy('mese')
            ->get();
    }
}
