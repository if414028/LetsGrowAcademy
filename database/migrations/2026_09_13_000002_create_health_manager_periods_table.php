<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        Schema::create('health_manager_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('started_on');
            $table->date('ended_on')->nullable();
            $table->unique(['user_id', 'started_on']);
        });
        // Only current HMs have a known open period. Past demotions require an explicit date.
        DB::table('users')->whereIn('id', function ($q) {
            $q->select('model_id')->from('model_has_roles')->join('roles', 'roles.id', '=', 'role_id')
                ->where('model_type', App\Models\User::class)->where('roles.name', 'Health Manager');
        })->orderBy('id')->chunkById(200, function ($users) {
            foreach ($users as $user) {
                DB::table('health_manager_periods')->insert([
                    'user_id' => $user->id,
                    'started_on' => substr($user->hm_since ?? $user->join_date ?? $user->created_at, 0, 10),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_manager_periods');
    }
};
