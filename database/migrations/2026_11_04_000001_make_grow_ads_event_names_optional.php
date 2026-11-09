<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['grow_ad_contents', 'grow_ad_placements'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->string('event_name', 120)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        foreach (['grow_ad_contents', 'grow_ad_placements'] as $name) {
            DB::table($name)->whereNull('event_name')->update(['event_name' => DB::raw('title')]);

            Schema::table($name, function (Blueprint $table) {
                $table->string('event_name', 120)->nullable(false)->change();
            });
        }
    }
};
