<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Support\AppVersion;
use Illuminate\Support\Facades\Schema;

class RecordDeployCommand extends Command
{
    protected $signature = 'deploy:record {--notes=}';
    protected $description = 'Registra un deploy nella tabella deploy_history (audit)';

    public function handle(): int
    {
        $version = AppVersion::version();
        $commit = AppVersion::commit();
        $mode = config('praticavoice.mode', 'cloud');
        $notes = $this->option('notes');

        if (!Schema::hasTable('deploy_history')) {
            $this->error('Tabella deploy_history non trovata. Esegui le migrazioni.');
            return self::FAILURE;
        }

        DB::table('deploy_history')->insert([
            'version' => $version,
            'commit' => $commit,
            'mode' => $mode,
            'notes' => $notes,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->info("Deploy registrato: {$version} ({$commit}) mode={$mode}");
        return self::SUCCESS;
    }
}
