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
        Schema::table('pratiche', function (Blueprint $table) {
            $table->integer('numero_pdf')->nullable()->after('cartella');
        });
    }

    public function down()
    {
        Schema::table('pratiche', function (Blueprint $table) {
            $table->dropColumn('numero_pdf');
        });
    }

};
