<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deploy_history', function (Blueprint $table) {
            $table->id();
            $table->string('version', 100)->nullable();
            $table->string('commit', 100)->nullable();
            $table->string('mode', 20)->nullable();
            $table->string('notes', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deploy_history');
    }
};
