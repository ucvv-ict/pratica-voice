<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accessi_atti', function (Blueprint $table) {
            $table->text('r2_link')->nullable()->after('note');
            $table->timestamp('r2_link_generated_at')->nullable()->after('r2_link');
            $table->timestamp('r2_link_expires_at')->nullable()->after('r2_link_generated_at');
        });
    }

    public function down(): void
    {
        Schema::table('accessi_atti', function (Blueprint $table) {
            $table->dropColumn(['r2_link', 'r2_link_generated_at', 'r2_link_expires_at']);
        });
    }
};
