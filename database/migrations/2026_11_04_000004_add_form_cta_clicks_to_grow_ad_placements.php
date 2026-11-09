<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grow_ad_placements', function (Blueprint $table) {
            $table->unsignedBigInteger('form_cta_clicks')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('grow_ad_placements', function (Blueprint $table) {
            $table->dropColumn('form_cta_clicks');
        });
    }
};
