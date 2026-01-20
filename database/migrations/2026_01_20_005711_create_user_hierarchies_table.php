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
        Schema::create('user_hierarchies', function (Blueprint $table) {
            $table->id();

            // user yang merefer / atasan
            $table->foreignId('parent_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // user yang direfer / bawahan
            $table->foreignId('child_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // jenis relasi (optional, future-proof)
            $table->string('relation_type')->default('referral');

            $table->timestamps();

            // 1 user hanya boleh punya 1 atasan langsung
            $table->unique('child_user_id');

            // index untuk query "siapa bawahan saya"
            $table->index('parent_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_hierarchies');
    }
};
