<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contest_winners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contest_id')
                ->constrained('contests')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->unsignedInteger('rank')->default(1);
            $table->unsignedInteger('final_installed_units')->default(0);
            $table->boolean('reached_target')->default(false);

            $table->timestamp('computed_at')->useCurrent();

            // 1 user cuma boleh jadi winner 1x di contest yang sama
            $table->unique(['contest_id', 'user_id']);

            // rank tidak boleh double di contest yang sama
            $table->unique(['contest_id', 'rank']);

            $table->index('computed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contest_winners');
    }
};
