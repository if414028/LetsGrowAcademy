<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recruitment_leads', function (Blueprint $table) {
            $table->string('seminar_id', 80)->nullable();
            $table->string('seminar_name', 160)->nullable();
            $table->string('batch_id', 80)->nullable();
            $table->timestamp('batch_starts_at')->nullable();
            $table->timestamp('whatsapp_consent_revoked_at')->nullable();
        });

        Schema::table('crm_webhook_deliveries', function (Blueprint $table) {
            $table->dropForeign(['recruitment_lead_id']);
        });
        Schema::table('crm_webhook_deliveries', function (Blueprint $table) {
            $table->unsignedBigInteger('recruitment_lead_id')->nullable()->change();
            $table->foreign('recruitment_lead_id')->references('id')->on('recruitment_leads')->cascadeOnDelete();
        });

        // No participant PII: this survives deletion and supports GET recovery.
        Schema::create('crm_registration_tombstones', function (Blueprint $table) {
            $table->id();
            $table->string('registration_id', 40)->unique();
            $table->string('environment', 80);
            $table->timestamp('deleted_at')->index();
            $table->unsignedBigInteger('last_event_sequence')->default(0);
        });
    }

    public function down(): void
    {
        // Older code cannot deliver events without a registration relationship.
        DB::table('crm_webhook_deliveries')->whereNull('recruitment_lead_id')->delete();
        Schema::dropIfExists('crm_registration_tombstones');
        Schema::table('crm_webhook_deliveries', function (Blueprint $table) {
            $table->dropForeign(['recruitment_lead_id']);
        });
        Schema::table('crm_webhook_deliveries', function (Blueprint $table) {
            $table->unsignedBigInteger('recruitment_lead_id')->nullable(false)->change();
            $table->foreign('recruitment_lead_id')->references('id')->on('recruitment_leads')->cascadeOnDelete();
        });
        Schema::table('recruitment_leads', function (Blueprint $table) {
            $table->dropColumn(['seminar_id', 'seminar_name', 'batch_id', 'batch_starts_at', 'whatsapp_consent_revoked_at']);
        });
    }
};
