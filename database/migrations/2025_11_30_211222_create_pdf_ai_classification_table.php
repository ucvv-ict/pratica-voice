<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pdf_ai_classification', function (Blueprint $table) {
            $table->id();

            // 🔗 sempre al pdf_index
            $table->unsignedBigInteger('pdf_index_id');

            // NULL = classificazione intero documento, 
            // valore = classificazione pagina specifica
            $table->unsignedInteger('page')->nullable();

            // JSON con tipo_documento, temi, persone, indirizzi, ecc.
            $table->json('classification')->nullable();

            // piccolo riassunto testuale
            $table->text('summary')->nullable();

            $table->timestamps();

            $table->foreign('pdf_index_id')
                ->references('id')->on('pdf_index')
                ->onDelete('cascade');

            $table->index(['pdf_index_id', 'page']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdf_ai_classification');
    }
};
