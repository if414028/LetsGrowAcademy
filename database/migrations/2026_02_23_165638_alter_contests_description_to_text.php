<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ubah description dari VARCHAR -> TEXT
        DB::statement("ALTER TABLE contests MODIFY description TEXT NULL");
    }

    public function down(): void
    {
        // Balikin ke VARCHAR(255) kalau perlu rollback
        DB::statement("ALTER TABLE contests MODIFY description VARCHAR(255) NULL");
    }
};
