<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recruitment_event_payments', function (Blueprint $table) {
            $table->string('selected_payment_method', 60)->nullable();
            $table->json('pricing_details')->nullable();
            $table->text('snap_token')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('recruitment_event_payments', function (Blueprint $table) {
            $table->dropColumn(['selected_payment_method', 'pricing_details', 'snap_token']);
        });
    }
};
