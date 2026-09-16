<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('landing_page_slug')->nullable()->after('name');
        });

        $used = [];

        DB::table('users')->orderBy('id')->select(['id', 'name', 'dst_code'])->chunkById(200, function ($users) use (&$used): void {
            foreach ($users as $user) {
                $base = Str::slug(collect([$user->name, $user->dst_code])->filter()->implode('-')) ?: 'member';
                $slug = $base;
                $suffix = 2;

                while (isset($used[$slug]) || DB::table('users')->where('landing_page_slug', $slug)->exists()) {
                    $slug = $base.'-'.$suffix++;
                }

                DB::table('users')->where('id', $user->id)->update(['landing_page_slug' => $slug]);
                $used[$slug] = true;
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unique('landing_page_slug');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['landing_page_slug']);
            $table->dropColumn('landing_page_slug');
        });
    }
};
