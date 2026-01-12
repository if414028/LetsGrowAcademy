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
        Schema::create('sales_kpi_dailies', function (Blueprint $table) {
            $table->id();
            $table->date('kpi_date');

            $table->foreignId('sales_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->unsignedInteger('units')->default(0);
            $table->unsignedInteger('order_count')->default(0);
            $table->unsignedInteger('installed_count')->default(0);
            $table->unsignedInteger('ccp_approved_count')->default(0);
            $table->unsignedInteger('recurring_count')->default(0);

            $table->timestamps();

            // 1 KPI per sales per hari
            $table->unique(['kpi_date', 'sales_user_id']);

            $table->index('kpi_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_kpi_dailies');
    }
};
