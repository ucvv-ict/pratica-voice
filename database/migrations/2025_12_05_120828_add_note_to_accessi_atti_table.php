<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accessi_atti', function (Blueprint $table) {
            $table->text('note')
                ->nullable()
                ->after('descrizione');
        });
    }

    public function down(): void
    {
        Schema::table('accessi_atti', function (Blueprint $table) {
            $table->dropColumn('note');
        });
    }
};
