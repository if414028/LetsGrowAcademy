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
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();

            //Core Identity
            $table->string('order_no')->unique();

            //Relation
            $table->foreignId('sales_user_id')
            ->constrained('users')
            ->cascadeOnDelete();

            $table->foreignId('customer_id')
            ->constrained('customers')
            ->cascadeOnDelete();

            $table->timestamp('key_in_at')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->string('ccp_status')->default('pending');
            $table->date('install_date')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('status');

            $table-> softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
