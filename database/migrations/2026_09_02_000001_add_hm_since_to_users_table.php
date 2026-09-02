<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->date('hm_since')->nullable()->after('join_date')->index();
        });

        DB::table('users')
            ->whereIn('id', function ($query) {
                $query->select('model_has_roles.model_id')
                    ->from('model_has_roles')
                    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                    ->where('model_has_roles.model_type', \App\Models\User::class)
                    ->where('roles.name', 'Health Manager');
            })
            ->update(['hm_since' => DB::raw('COALESCE(join_date, DATE(created_at))')]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['hm_since']);
            $table->dropColumn('hm_since');
        });
    }
};
