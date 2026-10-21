<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('source', 40);
            $table->string('source_slug');
            $table->string('source_url', 2048);
            $table->string('full_name', 120);
            $table->string('phone_number', 20);
            $table->string('domicile', 120);
            $table->string('activity', 120);
            $table->string('interest', 180)->nullable();
            $table->timestamp('consented_at');
            $table->timestamps();

            $table->index(['owner_user_id', 'source', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_leads');
    }
};
