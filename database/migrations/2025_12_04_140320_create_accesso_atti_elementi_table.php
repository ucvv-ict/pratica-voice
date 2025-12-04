<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accesso_atti_elementi', function (Blueprint $table) {
            $table->id();

            // Fascicolo
            $table->unsignedBigInteger('accesso_atti_id');
            $table->foreign('accesso_atti_id')
                ->references('id')->on('accessi_atti')
                ->cascadeOnDelete();

            // Tipo elemento
            $table->enum('tipo', ['file_pratica', 'file_esterno']);

            // 🔵 FILE PRATICA → corretto binding a pdf_files.id
            $table->unsignedBigInteger('file_id')->nullable();
            $table->foreign('file_id')
                ->references('id')->on('pdf_files')
                ->nullOnDelete();

            // 🔵 FILE ESTERNO (solo path, nessun FK)
            $table->string('file_esterno_path')->nullable();

            // Range pagine
            $table->unsignedInteger('pagina_inizio')->nullable();
            $table->unsignedInteger('pagina_fine')->nullable();

            // Ordinamento
            $table->unsignedInteger('ordinamento');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accesso_atti_elementi');
    }
};
