<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('midtrans_order_id')->nullable()->unique()->after('amount');
            $table->string('midtrans_transaction_id')->nullable()->index()->after('midtrans_order_id');
            $table->string('payment_status', 30)->default('pending')->index()->after('midtrans_transaction_id');
            $table->string('payment_type')->nullable()->after('payment_status');
            $table->string('snap_token')->nullable()->after('payment_type');
            $table->timestamp('paid_at')->nullable()->after('snap_token');
            $table->json('payment_payload')->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropUnique(['midtrans_order_id']);
            $table->dropIndex(['midtrans_transaction_id']);
            $table->dropIndex(['payment_status']);
            $table->dropColumn([
                'midtrans_order_id',
                'midtrans_transaction_id',
                'payment_status',
                'payment_type',
                'snap_token',
                'paid_at',
                'payment_payload',
            ]);
        });
    }
};
