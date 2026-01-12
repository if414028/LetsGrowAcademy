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
        Schema::create('contest_progress_dailies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contest_id')
                ->constrained('contests')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->date('progress_date');
            $table->unsignedInteger('installed_units')->default(0);

            // sesuai ERD cuma created_at
            $table->timestamp('created_at')->useCurrent();

            // 1 record per user per day per contest
            $table->unique(['contest_id', 'user_id', 'progress_date']);

            $table->index('progress_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contest_progress_dailies');
    }
};
