<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('selling_kit_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('color', 20)->default('sky');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('selling_kit_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('selling_kit_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type', 150);
            $table->string('extension', 12);
            $table->unsignedBigInteger('file_size');
            $table->timestamps();
        });

        $now = now();
        $categories = [
            ['name' => 'Katalog & Edukasi Produk', 'slug' => 'katalog-edukasi-produk', 'description' => 'Materi produk dan edukasi yang dapat dibagikan kepada calon customer.', 'color' => 'sky', 'sort_order' => 1],
            ['name' => 'Panduan Penjualan', 'slug' => 'panduan-penjualan', 'description' => 'Panduan operasional untuk membantu proses konfirmasi dan key in penjualan.', 'color' => 'emerald', 'sort_order' => 2],
            ['name' => 'Rekrutmen Health Planner', 'slug' => 'rekrutmen-health-planner', 'description' => 'Materi untuk menjelaskan peluang dan alur bergabung sebagai Health Planner.', 'color' => 'violet', 'sort_order' => 3],
        ];

        foreach ($categories as $category) {
            DB::table('selling_kit_categories')->insert($category + ['created_at' => $now, 'updated_at' => $now]);
        }

        $categoryIds = DB::table('selling_kit_categories')->pluck('id', 'slug');
        $documents = [
            ['category' => 'katalog-edukasi-produk', 'slug' => 'catalog-september-2026', 'title' => 'Catalog September 2026', 'description' => 'Katalog produk Coway edisi September 2026.', 'file' => 'selling-kit/catalog-september-2026.pdf', 'original' => 'Catalog Sept 26 lowres.pdf', 'size' => 15204352],
            ['category' => 'katalog-edukasi-produk', 'slug' => 'waspada-abu-vulkanik', 'title' => 'Waspada Abu Vulkanik', 'description' => 'Materi edukasi mengenai kualitas udara dan abu vulkanik.', 'file' => 'selling-kit/waspada-abu-vulkanik.pdf', 'original' => 'Waspada Abu Vulkanik.pdf', 'size' => 12163482],
            ['category' => 'panduan-penjualan', 'slug' => 'customer-confirmation', 'title' => 'Customer Confirmation', 'description' => 'Panduan customer confirmation dalam proses penjualan.', 'file' => 'selling-kit/customer-confirmation.pdf', 'original' => 'Customer Confirmation.pdf', 'size' => 2411725],
            ['category' => 'panduan-penjualan', 'slug' => 'alur-key-in', 'title' => 'Alur Key In', 'description' => 'Alur dan tahapan key in untuk proses administrasi penjualan.', 'file' => 'selling-kit/alur-key-in.pdf', 'original' => 'Alur Key In-.pdf', 'size' => 3565158],
            ['category' => 'rekrutmen-health-planner', 'slug' => 'alur-rekrut-calon-hp', 'title' => 'Alur Rekrut Calon HP', 'description' => 'Panduan tahapan rekrutmen calon Health Planner.', 'file' => 'selling-kit/alur-rekrut-calon-hp.pdf', 'original' => 'Alur Rekrut (Calon HP) rev 3.pdf', 'size' => 12687769],
            ['category' => 'rekrutmen-health-planner', 'slug' => 'brosur-lga-september-2026', 'title' => 'Brosur LGA September 2026', 'description' => 'Brosur Let\'s Grow Academy edisi September 2026.', 'file' => 'selling-kit/brosur-lga-september-2026.pdf', 'original' => 'Brosur LGA sept 26.pdf', 'size' => 6920602],
        ];

        foreach ($documents as $document) {
            DB::table('selling_kit_documents')->insert([
                'selling_kit_category_id' => $categoryIds[$document['category']],
                'title' => $document['title'], 'slug' => $document['slug'], 'description' => $document['description'],
                'file_path' => $document['file'], 'original_name' => $document['original'],
                'mime_type' => 'application/pdf', 'extension' => 'pdf', 'file_size' => $document['size'],
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('selling_kit_documents');
        Schema::dropIfExists('selling_kit_categories');
    }
};
