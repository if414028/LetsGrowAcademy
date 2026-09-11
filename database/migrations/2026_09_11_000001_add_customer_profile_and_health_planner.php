<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('religion', 100)->nullable();
            $table->string('unit_serial_number')->nullable();
            $table->string('ktp_path')->nullable();
            $table->string('unit_barcode_path')->nullable();
            $table->foreignId('health_planner_id')->nullable()->constrained('users')->nullOnDelete();
        });

        // Existing customers start with the HP from their most recently created SO.
        DB::table('customers')->orderBy('id')->chunkById(200, function ($customers) {
            foreach ($customers as $customer) {
                $hpId = DB::table('sales_orders')->where('customer_id', $customer->id)
                    ->orderByDesc('id')->value('sales_user_id');
                if ($hpId) {
                    DB::table('customers')->where('id', $customer->id)->update(['health_planner_id' => $hpId]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('health_planner_id');
            $table->dropColumn(['religion', 'unit_serial_number', 'ktp_path', 'unit_barcode_path']);
        });
    }
};
