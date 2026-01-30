<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            $table->string('label'); // contoh: Product Price, Package 36, Filter 12 Full
            $table->enum('billing_type', ['one_time', 'monthly'])->default('one_time');
            $table->unsignedSmallInteger('duration_months')->nullable(); // 36/60/72/84 untuk package
            $table->decimal('amount', 15, 2);

            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['product_id', 'billing_type', 'duration_months']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_prices');
    }
};
