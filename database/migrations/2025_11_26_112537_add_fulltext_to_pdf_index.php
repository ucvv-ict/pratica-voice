<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE pdf_index ADD FULLTEXT(content)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE pdf_index DROP INDEX content');
    }
};
