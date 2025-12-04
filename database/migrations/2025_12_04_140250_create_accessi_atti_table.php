<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accessi_atti', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pratica_id');
            $table->unsignedInteger('versione');
            $table->string('descrizione')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('pratica_id')
                ->references('id')->on('pratiche')
                ->cascadeOnDelete();

            $table->foreign('created_by')
                ->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accessi_atti');
    }
};
