<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('pdf_index', function (Blueprint $table) {
            $table->string('hash')->after('content');
        });
    }

    public function down()
    {
        Schema::table('pdf_index', function (Blueprint $table) {
            $table->dropColumn('hash');
        });
    }
};
