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
        Schema::create('contest_eligibility_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contest_id')
                ->constrained('contests')
                ->cascadeOnDelete();

            $table->foreignId('eligible_role_id')
                ->constrained('roles')
                ->restrictOnDelete();

            // cegah role yang sama dimasukkan dua kali untuk kontes yang sama
            $table->unique(['contest_id', 'eligible_role_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contest_eligibility_roles');
    }
};
