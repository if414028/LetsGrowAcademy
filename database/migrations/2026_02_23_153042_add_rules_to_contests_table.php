<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contests', function (Blueprint $table) {

            // Metric perhitungan (ns = count sales order, unit_qty = sum qty)
            $table->string('metric')
                ->default('unit_qty')
                ->after('target_unit');

            // Field tanggal yang dijadikan basis perhitungan
            $table->string('date_basis')
                ->default('install_date')
                ->after('metric');

            // Rules fleksibel (untuk kontes tipe 133 dll)
            $table->json('rules')
                ->nullable()
                ->after('date_basis');

            // Optional: tipe kontes (leaderboard / qualifier)
            $table->string('type')
                ->default('leaderboard')
                ->after('rules');
        });
    }

    public function down(): void
    {
        Schema::table('contests', function (Blueprint $table) {
            $table->dropColumn([
                'metric',
                'date_basis',
                'rules',
                'type',
            ]);
        });
    }
};
