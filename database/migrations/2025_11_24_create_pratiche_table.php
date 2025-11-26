<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pratiche', function (Blueprint $table) {
            $table->id();

            $table->integer('anno_presentazione')->nullable();
            $table->string('data_protocollo')->nullable();
            $table->string('numero_protocollo')->nullable();
            $table->text('oggetto')->nullable();
            $table->string('numero_pratica')->nullable();
            $table->string('data_rilascio')->nullable();
            $table->string('numero_rilascio')->nullable();
            $table->string('riferimento_libero')->nullable();

            $table->string('area_circolazione')->nullable();
            $table->string('civico_esponente')->nullable();

            $table->string('codice_catasto')->nullable();
            $table->string('tipo_catasto')->nullable();
            $table->string('sezione')->nullable();
            $table->string('foglio')->nullable();
            $table->string('particella_sub')->nullable();

            $table->text('nota')->nullable();

            $table->string('rich_cognome1')->nullable();
            $table->string('rich_nome1')->nullable();
            $table->string('rich_cognome2')->nullable();
            $table->string('rich_nome2')->nullable();
            $table->string('rich_cognome3')->nullable();
            $table->string('rich_nome3')->nullable();

            $table->string('sigla_tipo_pratica')->nullable();
            $table->string('pratica_id')->nullable();

            // campo che useremo per puntare alla cartella sul file server
            $table->string('cartella')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pratiche');
    }
};

