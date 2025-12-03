<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pdf_ai_embeddings', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('pdf_index_id');

            // NULL = embedding documento intero, 
            // valore = embedding pagina specifica
            $table->unsignedInteger('page')->nullable();

            // vettore embedding OpenAI come JSON
            $table->json('embedding');

            $table->timestamps();

            $table->foreign('pdf_index_id')
                ->references('id')->on('pdf_index')
                ->onDelete('cascade');

            $table->index(['pdf_index_id', 'page']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdf_ai_embeddings');
    }
};
