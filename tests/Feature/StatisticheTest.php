<?php

namespace Tests\Feature;

use App\Models\Pratica;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StatisticheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
        Schema::dropIfExists('fascicoli_generazione');
        Schema::dropIfExists('pratiche');

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
