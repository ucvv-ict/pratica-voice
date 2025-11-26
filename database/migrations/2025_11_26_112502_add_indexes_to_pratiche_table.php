<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pratiche', function (Blueprint $table) {

            // Ricerca base
            $table->index('anno_presentazione');
            $table->index('numero_pratica');
            $table->index('rich_cognome1');
            $table->index('rich_nome1');
            $table->index('area_circolazione');

            // Catasto
            $table->index('foglio');
            $table->index('particella_sub');

            // Protocollo / Rilascio
            $table->index('data_protocollo');
            $table->index('data_rilascio');

            // Tipo pratica
            $table->index('sigla_tipo_pratica');

            // Cartella (per accesso rapido ai PDF)
            $table->index('cartella');
        });
    }

    public function down(): void
    {
        Schema::table('pratiche', function (Blueprint $table) {

            $table->dropIndex(['anno_presentazione']);
            $table->dropIndex(['numero_pratica']);
            $table->dropIndex(['rich_cognome1']);
            $table->dropIndex(['rich_nome1']);
            $table->dropIndex(['area_circolazione']);

            $table->dropIndex(['foglio']);
            $table->dropIndex(['particella_sub']);

            $table->dropIndex(['data_protocollo']);
            $table->dropIndex(['data_rilascio']);

            $table->dropIndex(['sigla_tipo_pratica']);

            $table->dropIndex(['cartella']);
        });
    }
};
