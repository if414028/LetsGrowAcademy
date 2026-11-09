<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grow_ad_placements', function (Blueprint $table) {
            $table->string('pixel_id', 30)->nullable();
        });

        $campaignPixel = DB::table('grow_ad_campaigns')->select('pixel_id')
            ->whereColumn('grow_ad_campaigns.id', 'grow_ad_placements.campaign_id');

        DB::table('grow_ad_placements')->update(['pixel_id' => $campaignPixel]);
    }

    public function down(): void
    {
        Schema::table('grow_ad_placements', function (Blueprint $table) {
            $table->dropColumn('pixel_id');
        });
    }
};
