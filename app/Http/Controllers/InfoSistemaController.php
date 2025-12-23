<?php

namespace App\Http\Controllers;

use App\Support\AppVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class InfoSistemaController extends Controller
{
    public function index(Request $request)
    {
        // Accesso solo admin/autenticati; se non autenticato → 403
        if (!auth()->check()) {
            abort(403);
        }

        $user = auth()->user();
        if (method_exists($user, 'is_admin') && !$user->is_admin) {
            abort(403);
        }

        $appInfo = [
            'name' => config('app.name'),
            'version' => AppVersion::version(),
            'mode' => Str::of(config('praticavoice.mode', 'cloud'))->replace('_', '-')->upper(),
            'env' => config('app.env'),
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
        if ($recentlyReserved !== null) {
            $workerStatus = $recentlyReserved > 0 ? 'active' : 'inactive';
        }

        return view('info-sistema', [
            'appInfo' => $appInfo,
            'systemInfo' => $systemInfo,
            'queueConnection' => $queueConnection,
            'pendingJobs' => $pendingJobs,
            'failedJobs' => $failedJobs,
            'workerStatus' => $workerStatus,
            'queueNote' => $queueNote,
        ]);
    }
}
