<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recruitment_leads', function (Blueprint $table) {
            // NULL preserves the unknown WhatsApp consent of historical registrations.
            $table->boolean('whatsapp_consent')->nullable();
            $table->timestamp('whatsapp_consented_at')->nullable();
            $table->string('whatsapp_consent_version', 30)->nullable();
            $table->index(['source', 'updated_at']);
        });

        Schema::table('recruitment_event_payments', function (Blueprint $table) {
            $table->string('midtrans_transaction_id')->nullable()->index();
            $table->timestamp('paid_at')->nullable();
        });

        Schema::create('crm_webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_id')->unique();
            $table->foreignId('recruitment_lead_id')->constrained()->cascadeOnDelete();
            $table->string('event_type', 40);
            $table->json('payload');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('next_attempt_at')->nullable();
            $table->uuid('lease_token')->nullable();
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->unsignedSmallInteger('last_http_status')->nullable();
            $table->string('last_error_code', 40)->nullable();
            $table->timestamps();
            $table->index(['delivered_at', 'next_attempt_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_webhook_deliveries');
        Schema::table('recruitment_event_payments', function (Blueprint $table) {
            $table->dropIndex(['midtrans_transaction_id']);
            $table->dropColumn(['midtrans_transaction_id', 'paid_at']);
        });
        Schema::table('recruitment_leads', function (Blueprint $table) {
            $table->dropIndex(['source', 'updated_at']);
            $table->dropColumn(['whatsapp_consent', 'whatsapp_consented_at', 'whatsapp_consent_version']);
        });
    }
};
