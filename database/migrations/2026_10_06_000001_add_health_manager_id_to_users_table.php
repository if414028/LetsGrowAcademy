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
            $table->foreignId('health_manager_id')->nullable()->constrained('users')->nullOnDelete();
        });

        // Snapshot the currently known HM without altering referral relationships.
        $roles = DB::table('model_has_roles')->join('roles', 'roles.id', '=', 'role_id')
            ->where('model_type', App\Models\User::class)->get(['model_id', 'roles.name']);
        $hmIds = $roles->where('name', 'Health Manager')->pluck('model_id')->flip();
        $parentByChild = DB::table('user_hierarchies')->pluck('parent_user_id', 'child_user_id');
        foreach ($roles->where('name', 'Health Planner')->pluck('model_id')->unique() as $plannerId) {
            $current = (int) $plannerId;
            $seen = [$current => true];
            while ($parentId = $parentByChild->get($current)) {
                $parentId = (int) $parentId;
                if (isset($seen[$parentId])) break;
                $seen[$parentId] = true;
                if ($hmIds->has($parentId)) {
                    DB::table('users')->where('id', $plannerId)->update(['health_manager_id' => $parentId]);
                    break;
                }
                $current = $parentId;
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropConstrainedForeignId('health_manager_id'));
    }
};
