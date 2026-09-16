<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $used = [];

        // Bebaskan seluruh slug lama lebih dahulu agar perubahan massal tidak
        // berbenturan dengan unique index saat dua user saling bertukar nilai.
        DB::table('users')->orderBy('id')->select('id')->get()->each(
            fn ($user) => DB::table('users')->where('id', $user->id)
                ->update(['landing_page_slug' => '__migrating-'.$user->id])
        );

        DB::table('users')->orderBy('id')->select(['id', 'name', 'dst_code'])->get()->each(function ($user) use (&$used): void {
            $base = Str::slug(collect([$user->name, $user->dst_code])->filter()->implode('-')) ?: 'member';
            $slug = $base;
            $suffix = 2;

            while (isset($used[$slug])) {
                $slug = $base.'-'.$suffix++;
            }

            DB::table('users')->where('id', $user->id)->update(['landing_page_slug' => $slug]);
            $used[$slug] = true;
        });
    }

    public function down(): void
    {
        // Slug lama tidak dipulihkan karena URL harus tetap deterministik dan unik.
    }
};
