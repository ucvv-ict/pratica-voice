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
            ->assertSee('Fascicoli completati totali')
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
            ->assertSee('Andamento mensile');
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
