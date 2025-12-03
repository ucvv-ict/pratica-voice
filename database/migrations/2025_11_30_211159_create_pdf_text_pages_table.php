<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pdf_text_pages', function (Blueprint $table) {
            $table->id();

            // 🔗 Collega al singolo PDF in pdf_index
            $table->unsignedBigInteger('pdf_index_id');

            // pagina 1-based
            $table->unsignedInteger('page');

            // testo OCR grezzo da Vision
            $table->longText('text_ocr')->nullable();

            // versione eventualmente ripulita/normalizzata
            $table->longText('text_clean')->nullable();

            $table->timestamps();

            $table->foreign('pdf_index_id')
                ->references('id')->on('pdf_index')
                ->onDelete('cascade');

            $table->unique(['pdf_index_id', 'page']); // una riga per pagina
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdf_text_pages');
    }
};
