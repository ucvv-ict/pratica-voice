<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metadati_aggiornati', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pratica_id')->constrained('pratiche')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('versione');
            $table->json('dati');
            $table->timestamps();

            $table->unique(['pratica_id', 'versione']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metadati_aggiornati');
    }
};
