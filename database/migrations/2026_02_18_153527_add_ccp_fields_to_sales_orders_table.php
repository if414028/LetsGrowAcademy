<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->text('ccp_remarks')->nullable()->after('ccp_status');
            $table->dateTime('ccp_approved_at')->nullable()->after('ccp_remarks');
        });
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn(['ccp_remarks', 'ccp_approved_at']);
        });
    }
};
