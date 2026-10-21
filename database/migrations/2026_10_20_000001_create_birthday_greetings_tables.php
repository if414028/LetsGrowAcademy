<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('birthday_greeting_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('enabled')->default(false);
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('birthday_greeting_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'customer_id'], 'birthday_recipient_unique');
        });

        Schema::create('birthday_greeting_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->date('greeting_date');
            $table->string('status', 20);
            $table->string('provider_message_id')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            // A transfer to another owner must not send the same greeting twice.
            $table->unique(['customer_id', 'greeting_date'], 'birthday_delivery_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('birthday_greeting_deliveries');
        Schema::dropIfExists('birthday_greeting_recipients');
        Schema::dropIfExists('birthday_greeting_settings');
    }
};
