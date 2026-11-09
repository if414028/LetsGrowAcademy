<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recruitment_leads', function (Blueprint $table) {
            $table->uuid('event_checkout_id')->nullable()->unique();
            $table->timestamp('registration_pixel_sent_at')->nullable();
        });

        Schema::create('recruitment_event_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recruitment_lead_id')->constrained()->cascadeOnDelete();
            $table->string('midtrans_order_id', 50)->unique();
            $table->unsignedInteger('class_amount');
            $table->unsignedInteger('service_fee');
            $table->text('checkout_url');
            $table->string('status', 20)->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_event_payments');

        Schema::table('recruitment_leads', function (Blueprint $table) {
            $table->dropUnique(['event_checkout_id']);
            $table->dropColumn(['event_checkout_id', 'registration_pixel_sent_at']);
        });
    }
};
