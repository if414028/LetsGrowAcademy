<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_order_items', function (Blueprint $table) {
            if (!$this->indexExists('sales_order_items_product_id_index')) {
                $table->index('product_id', 'sales_order_items_product_id_index');
            }

            if (!$this->indexExists('sales_order_items_sales_order_id_index')) {
                $table->index('sales_order_id', 'sales_order_items_sales_order_id_index');
            }
        });

        Schema::table('sales_order_items', function (Blueprint $table) {
            $table->dropUnique(['sales_order_id', 'product_id']);

            $table->foreignId('parent_item_id')
                ->nullable()
                ->after('sales_order_id')
                ->constrained('sales_order_items')
                ->cascadeOnDelete();

            $table->boolean('is_cancelled')
                ->default(false)
                ->after('qty');
        });
    }

    public function down(): void
    {
        Schema::table('sales_order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_item_id');
            $table->dropColumn('is_cancelled');

            $table->unique(['sales_order_id', 'product_id']);
            if ($this->indexExists('sales_order_items_product_id_index')) {
                $table->dropIndex('sales_order_items_product_id_index');
            }
            if ($this->indexExists('sales_order_items_sales_order_id_index')) {
                $table->dropIndex('sales_order_items_sales_order_id_index');
            }
        });
    }

    private function indexExists(string $indexName): bool
    {
        return collect(DB::select('SHOW INDEX FROM sales_order_items'))
            ->contains(fn($index) => $index->Key_name === $indexName);
    }
};
