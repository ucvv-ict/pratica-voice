<?php

namespace App\Http\Controllers;

use App\Support\AppVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Support\Tenant;

class InfoSistemaController extends Controller
{
    public function index(Request $request)
    {
        // Nota: in futuro proteggere con autenticazione/admin; attualmente accesso libero per diagnostica.

        $appInfo = [
            'name' => config('app.name'),
            'version' => AppVersion::version(),
            'commit' => AppVersion::commit(),
            'mode' => Str::of(config('praticavoice.mode', 'cloud'))->replace('_', '-')->upper(),
            'env' => config('app.env'),
            'tenant' => Tenant::name(),
        ];

        $systemInfo = [
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
            'hostname' => gethostname(),
            'pdf_base_path' => config('praticavoice.mode') === 'on_prem' ? config('pratica.pdf_base_path') : null,
        ];

        $queueConnection = config('queue.default');
        $pendingJobs = null;
        $failedJobs = null;
        $recentlyReserved = null;
        $queueNote = null;

        try {
            if (Schema::hasTable('jobs')) {
                $pendingJobs = DB::table('jobs')->count();
                $recentlyReserved = DB::table('jobs')
                    ->whereNotNull('reserved_at')
                    ->where('reserved_at', '>', now()->subMinutes(10)->getTimestamp())
                    ->count();
            } else {
                $queueNote = 'Tabella jobs non trovata (eseguire php artisan queue:table && migrate).';
            }

            if (Schema::hasTable('failed_jobs')) {
                $failedJobs = DB::table('failed_jobs')->count();
            }
        } catch (\Throwable $e) {
            $queueNote = 'Impossibile leggere stato queue: ' . $e->getMessage();
        }

        $workerStatus = 'unknown';
        $heartbeatPath = '/var/run/praticavoice/queue.last_seen';
        $heartbeatMinutes = null;

        if (file_exists($heartbeatPath)) {
            $heartbeatMinutes = (time() - filemtime($heartbeatPath)) / 60;
        }

        if ($recentlyReserved !== null && $recentlyReserved > 0) {
            $workerStatus = 'active';
        } elseif ($heartbeatMinutes !== null && $heartbeatMinutes <= 10) {
            $workerStatus = 'active';
        } elseif ($heartbeatMinutes !== null) {
            $workerStatus = 'inactive';
        }

        $deployHistory = [];
        if (Schema::hasTable('deploy_history')) {
            $deployHistory = DB::table('deploy_history')
                ->orderByDesc('created_at')
                ->limit(5)
                ->get();
        }

        return view('info-sistema', [
            'appInfo' => $appInfo,
            'systemInfo' => $systemInfo,
            'queueConnection' => $queueConnection,
            'pendingJobs' => $pendingJobs,
            'failedJobs' => $failedJobs,
            'workerStatus' => $workerStatus,
            'queueNote' => $queueNote,
            'heartbeatMinutes' => $heartbeatMinutes,
            'deployHistory' => $deployHistory,
        ]);
    }
}
