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
        Schema::table('fascicoli_generazione', function (Blueprint $table) {
            $table->timestamp('notificato_at')->nullable()->after('files_selezionati');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fascicoli_generazione', function (Blueprint $table) {
            $table->dropColumn('notificato_at');
        });
    }
};
