<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pdf_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pratica_id')->nullable();
            $table->string('cartella', 50)->index();
            $table->string('file', 255);
            $table->string('md5', 64)->nullable()->index();
            $table->string('sha256', 128)->nullable()->index();
            $table->unsignedBigInteger('size_bytes')->nullable();

            // segnare PDF importanti
            $table->boolean('importante')->default(false);

            $table->timestamps();

            // relazione con pratiche
            $table->foreign('pratica_id')
                  ->references('id')
                  ->on('pratiche')
                  ->onDelete('cascade');

            $table->unique(['cartella', 'file']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdf_files');
    }
};
