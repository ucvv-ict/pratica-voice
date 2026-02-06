<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pratiche', function (Blueprint $table) {
            $table
                ->string('gruppo_bat', 255)
                ->nullable()
                ->after('cartella');

            $table->index('gruppo_bat');
        });
    }

    public function down(): void
    {
        Schema::table('pratiche', function (Blueprint $table) {
            $table->dropIndex(['gruppo_bat']);
            $table->dropColumn('gruppo_bat');
        });
    }
};

