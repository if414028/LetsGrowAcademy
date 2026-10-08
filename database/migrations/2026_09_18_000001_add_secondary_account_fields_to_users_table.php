<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_secondary_account')->default(false)->after('id_card');
            $table->foreignId('primary_account_id')
                ->nullable()
                ->after('is_secondary_account')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('primary_account_id');
            $table->dropColumn('is_secondary_account');
        });
    }
};
