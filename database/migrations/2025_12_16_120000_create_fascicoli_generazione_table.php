<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fascicoli_generazione', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pratica_id')->constrained('pratiche')->cascadeOnDelete();
            $table->integer('versione');
            $table->enum('stato', ['pending', 'in_progress', 'completed', 'error'])->default('pending');
            $table->integer('progress')->default(0);
            $table->text('errore')->nullable();
            $table->string('file_zip')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fascicoli_generazione');
    }
};
