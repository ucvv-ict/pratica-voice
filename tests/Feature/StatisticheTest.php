<?php

namespace Tests\Feature;

use App\Models\Pratica;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StatisticheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-08-24 12:00:00');

        Schema::create('pratiche', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
        });

        Schema::create('fascicoli_generazione', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pratica_id')->constrained('pratiche');
            $table->integer('versione');
            $table->string('stato');
            $table->integer('progress')->default(0);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('deploy_history');
        Schema::dropIfExists('fascicoli_generazione');
        Schema::dropIfExists('pratiche');
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_la_pagina_gestisce_una_tabella_vuota(): void
    {
        config(['praticavoice.tenant.name' => 'Comune di Pelago']);

        $this->get(route('statistiche.index'))
            ->assertOk()
            ->assertSee('Statistiche utilizzo — Comune di Pelago')
            ->assertSee('Nessuna generazione disponibile')
            ->assertSee('Fascicoli completati')
            ->assertSee('>0<', false);
    }

    public function test_mostra_riepilogo_e_statistiche_mensili(): void
    {
        $primaPratica = Pratica::create();
        $secondaPratica = Pratica::create();

        $this->genera($primaPratica, 'completed', '2026-01-05 10:00:00');
        $this->genera($primaPratica, 'completed', '2026-01-20 11:00:00');
        $this->genera($secondaPratica, 'error', '2026-01-21 12:00:00');
        $this->genera($secondaPratica, 'completed', '2026-02-03 09:00:00');

        $this->get(route('statistiche.index'))
            ->assertOk()
            ->assertSeeInOrder(['2026-01', '2026-02'])
            ->assertSee('05/01/2026 10:00')
            ->assertSee('03/02/2026 09:00')
            ->assertSee('Andamento per mese');
    }

    public function test_esporta_le_statistiche_mensili_in_csv(): void
    {
        $pratica = Pratica::create();
        $this->genera($pratica, 'completed', '2026-03-10 08:00:00');
        $this->genera($pratica, 'error', '2026-03-11 08:00:00');

        $response = $this->get(route('statistiche.csv'));

        $response->assertOk()
            ->assertDownload('statistiche-fascicoli.csv')
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->assertStringContainsString(
            "mese,fascicoli_completati,pratiche_distinte,errori,totale_generazioni\n2026-03,1,1,1,2",
            $response->streamedContent()
        );
    }

    public function test_il_periodo_filtra_card_grafico_e_tabella(): void
    {
        $pratica = Pratica::create();
        $this->genera($pratica, 'completed', '2026-01-10 08:00:00');
        $this->genera($pratica, 'error', '2026-02-10 08:00:00');

        $response = $this->get(route('statistiche.index', [
            'from' => '2026-02-01',
            'to' => '2026-02-28',
            'group' => 'month',
        ]));

        $response->assertOk()->assertSee('1 febbraio 2026 – 28 febbraio 2026');
        $this->assertSame(0, (int) $response->viewData('riepilogo')->fascicoli_completati);
        $this->assertSame(1, (int) $response->viewData('riepilogo')->errori);
        $this->assertSame(['2026-02'], $response->viewData('statistiche')->pluck('periodo')->all());
    }

    public function test_la_granularita_giornaliera_completa_i_giorni_senza_dati(): void
    {
        $pratica = Pratica::create();
        $this->genera($pratica, 'completed', '2026-06-02 08:00:00');

        $response = $this->get(route('statistiche.index', [
            'from' => '2026-06-01',
            'to' => '2026-06-03',
            'group' => 'day',
        ]));

        $statistiche = $response->viewData('statistiche');
        $this->assertSame(['2026-06-01', '2026-06-02', '2026-06-03'], $statistiche->pluck('periodo')->all());
        $this->assertSame([0, 1, 0], $statistiche->pluck('fascicoli_completati')->map(fn ($value) => (int) $value)->all());
        $response->assertSee('Giorno')->assertSee('02/06/2026');
    }

    public function test_raggruppa_le_settimane_da_lunedi_a_domenica(): void
    {
        $pratica = Pratica::create();
        $this->genera($pratica, 'completed', '2026-08-17 08:00:00');
        $this->genera($pratica, 'completed', '2026-08-23 08:00:00');
        $this->genera($pratica, 'completed', '2026-08-24 08:00:00');

        $response = $this->get(route('statistiche.index', ['group' => 'week']));
        $statistiche = $response->viewData('statistiche');

        $this->assertSame(['2026-08-17', '2026-08-24'], $statistiche->pluck('periodo')->all());
        $this->assertSame([2, 1], $statistiche->pluck('fascicoli_completati')->map(fn ($value) => (int) $value)->all());
        $response->assertSee('17–23 ago 2026');
    }

    public function test_il_mese_espone_il_drill_down_giornaliero(): void
    {
        $pratica = Pratica::create();
        $this->genera($pratica, 'completed', '2026-06-15 08:00:00');

        $response = $this->get(route('statistiche.index'));
        $url = $response->viewData('statistiche')->first()->drill_url;

        $this->assertStringContainsString('group=day', $url);
        $this->assertStringContainsString('from=2026-06-01', $url);
        $this->assertStringContainsString('to=2026-06-30', $url);
    }

    public function test_il_csv_rispetta_periodo_e_granularita(): void
    {
        $pratica = Pratica::create();
        $this->genera($pratica, 'completed', '2026-05-31 08:00:00');
        $this->genera($pratica, 'completed', '2026-06-02 08:00:00');

        $response = $this->get(route('statistiche.csv', [
            'from' => '2026-06-01',
            'to' => '2026-06-03',
            'group' => 'day',
        ]));
        $csv = $response->streamedContent();

        $this->assertStringContainsString('giorno,fascicoli_completati,pratiche_distinte,errori,totale_generazioni', $csv);
        $this->assertStringContainsString("2026-06-01,0,0,0,0\n2026-06-02,1,1,0,1\n2026-06-03,0,0,0,0", $csv);
        $this->assertStringNotContainsString('2026-05', $csv);
    }

    public function test_la_heatmap_copre_gli_ultimi_dodici_mesi_e_ignora_i_filtri(): void
    {
        $primaPratica = Pratica::create();
        $secondaPratica = Pratica::create();
        $this->genera($primaPratica, 'completed', '2026-07-10 08:00:00');
        $this->genera($secondaPratica, 'completed', '2026-07-10 09:00:00');
        $this->genera($primaPratica, 'error', '2026-07-10 10:00:00');

        $response = $this->get(route('statistiche.index', [
            'from' => '2026-01-01',
            'to' => '2026-01-31',
        ]));
        $heatmap = $response->viewData('heatmap');
        $day = $heatmap['days']->firstWhere('date', '2026-07-10');

        $this->assertSame('2025-08-25', $heatmap['from']->format('Y-m-d'));
        $this->assertSame('2026-08-24', $heatmap['to']->format('Y-m-d'));
        $this->assertSame(365, $heatmap['days']->where('in_range', true)->count());
        $this->assertSame(2, $day->completed);
        $this->assertSame(2, $day->pratiche_distinte);
        $response->assertSee('Attività giornaliera — ultimi 12 mesi');
    }

    public function test_il_grafico_cumulativo_completa_i_mesi_senza_attivita(): void
    {
        $pratica = Pratica::create();
        $this->genera($pratica, 'error', '2025-12-20 08:00:00');
        $this->genera($pratica, 'completed', '2026-01-05 08:00:00');
        $this->genera($pratica, 'completed', '2026-01-20 08:00:00');
        $this->genera($pratica, 'error', '2026-02-10 08:00:00');
        $this->genera($pratica, 'completed', '2026-03-01 08:00:00');

        $response = $this->get(route('statistiche.index', [
            'from' => '2026-08-01',
            'to' => '2026-08-24',
        ]));
        $cumulativo = $response->viewData('cumulativo');

        $this->assertSame(
            ['2025-12', '2026-01', '2026-02', '2026-03', '2026-04', '2026-05', '2026-06', '2026-07', '2026-08'],
            $cumulativo->pluck('periodo')->all()
        );
        $this->assertSame([0, 2, 2, 3, 3, 3, 3, 3, 3], $cumulativo->pluck('totale')->all());
        $response->assertSee('Fascicoli completati cumulativi');
    }

    public function test_heatmap_e_cumulativo_gestiscono_l_assenza_di_dati(): void
    {
        $response = $this->get(route('statistiche.index'));

        $this->assertTrue($response->viewData('cumulativo')->isEmpty());
        $this->assertSame(365, $response->viewData('heatmap')['days']->where('in_range', true)->count());
        $response->assertOk()->assertSee('Nessun fascicolo completato disponibile.');
    }

    public function test_la_manutenzione_gestisce_la_tabella_deploy_assente(): void
    {
        $response = $this->get(route('statistiche.index'));

        $response->assertOk()
            ->assertSee('Manutenzione tecnica')
            ->assertSee('La tabella di audit dei deploy non è presente');
        $this->assertFalse($response->viewData('manutenzione')['available']);
    }

    public function test_la_manutenzione_gestisce_la_tabella_deploy_vuota(): void
    {
        $this->createDeployHistoryTable();

        $response = $this->get(route('statistiche.index'));

        $response->assertOk()->assertSee('Nessun deploy automatico registrato');
        $this->assertTrue($response->viewData('manutenzione')['available']);
        $this->assertFalse($response->viewData('manutenzione')['has_data']);
    }

    public function test_la_manutenzione_usa_solo_deploy_automatici_e_gestisce_i_null(): void
    {
        $this->createDeployHistoryTable();
        $this->deploy('2026-08-20 10:00:00', '1234567890abcdef', 'deploy.sh');
        $this->deploy('2026-08-20 12:00:00', '1234567890abcdef', 'deploy.sh');
        $this->deploy('2026-07-10 10:00:00', null, 'deploy.sh');
        $this->deploy('2026-08-21 10:00:00', 'manuale123', 'manuale');
        $this->deploy(null, 'senza-data', 'deploy.sh');

        $response = $this->get(route('statistiche.index'));
        $manutenzione = $response->viewData('manutenzione');

        $this->assertSame(2, $manutenzione['ultimi_30_giorni']);
        $this->assertSame(2, $manutenzione['giorni_ultimi_90']);
        $this->assertSame(1, $manutenzione['commit_ultimi_90']);
        $this->assertSame(1, $manutenzione['commit_storici']);
        $this->assertSame('1234567890ab', $manutenzione['ultimo_deploy']->commit);
        $this->assertSame(['2026-07', '2026-08'], $manutenzione['mensili']->pluck('mese')->all());
        $response->assertDontSee('manuale123')->assertDontSee('senza-data');
    }

    public function test_il_filtro_data_si_applica_ai_deploy_mensili(): void
    {
        $this->createDeployHistoryTable();
        $this->deploy('2026-01-05 10:00:00', 'aaaa1111', 'deploy.sh');
        $this->deploy('2026-01-06 10:00:00', 'aaaa1111', 'deploy.sh');
        $this->deploy('2026-01-06 11:00:00', null, 'deploy.sh');
        $this->deploy('2026-02-05 10:00:00', 'bbbb2222', 'deploy.sh');

        $response = $this->get(route('statistiche.index', [
            'from' => '2026-01-01',
            'to' => '2026-01-31',
        ]));
        $manutenzione = $response->viewData('manutenzione');
        $row = $manutenzione['mensili']->sole();

        $this->assertSame('2026-01', $row->mese);
        $this->assertSame(3, (int) $row->registrazioni_deploy);
        $this->assertSame(1, (int) $row->commit_distinti);
        $this->assertSame(2, (int) $row->giorni_con_deploy);
        $this->assertSame(2, $manutenzione['commit_storici']);
    }

    public function test_le_label_del_grafico_sono_brevi_ma_i_tooltip_restano_completi(): void
    {
        $response = $this->get(route('statistiche.index', [
            'from' => '2026-07-26',
            'to' => '2026-08-24',
            'group' => 'day',
        ]));
        $giorni = $response->viewData('statistiche');

        $this->assertCount(30, $giorni);
        $this->assertSame('26/07', $giorni->first()->axis_label);
        $this->assertSame('26 luglio 2026', $giorni->first()->tooltip_label);
        $this->assertSame(30, substr_count($response->getContent(), 'class="x-axis-tick'));

        $pratica = Pratica::create();
        $this->genera($pratica, 'completed', '2025-12-15 08:00:00');

        $settimana = $this->get(route('statistiche.index', ['group' => 'week']))
            ->viewData('statistiche')
            ->first();
        $mese = $this->get(route('statistiche.index', ['group' => 'month']))
            ->viewData('statistiche')
            ->first();

        $this->assertSame('15–21 dic', $settimana->axis_label);
        $this->assertSame('15–21 dic 2025', $settimana->tooltip_label);
        $this->assertSame('2025-12', $mese->axis_label);
        $this->assertSame('dicembre 2025', $mese->tooltip_label);
    }

    private function createDeployHistoryTable(): void
    {
        Schema::create('deploy_history', function (Blueprint $table): void {
            $table->id();
            $table->string('version', 100)->nullable();
            $table->string('commit', 100)->nullable();
            $table->string('mode', 20)->nullable();
            $table->string('notes', 255)->nullable();
            $table->timestamps();
        });
    }

    private function deploy(?string $createdAt, ?string $commit, ?string $notes): void
    {
        DB::table('deploy_history')->insert([
            'version' => 'dev',
            'commit' => $commit,
            'mode' => 'cloud',
            'notes' => $notes,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    private function genera(Pratica $pratica, string $stato, string $createdAt): void
    {
        DB::table('fascicoli_generazione')->insert([
            'pratica_id' => $pratica->id,
            'versione' => 1,
            'stato' => $stato,
            'progress' => $stato === 'completed' ? 100 : 0,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}
