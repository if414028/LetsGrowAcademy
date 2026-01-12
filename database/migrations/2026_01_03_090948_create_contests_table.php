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
        Schema::create('contests', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('description')->nullable();

            $table->date('start_date');
            $table->date('end_date');

            $table->unsignedInteger('target_unit')->default(0);

            $table->string('reward')->nullable();
            $table->string('banner_url')->nullable();

            $table->foreignId('created_by_user_id')
                ->constrained('users')
                ->restrictOnDelete(); // jangan hapus user jika kontes sudah ada

            $table->foreignId('created_by_role_id')
                ->constrained('roles')
                ->restrictOnDelete();

            $table->string('status')->default('draft'); 
            // draft | published | closed

            $table->timestamps();

            $table->index(['start_date', 'end_date']);
            $table->index('status');
            $table->index('created_by_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contests');
    }
};
