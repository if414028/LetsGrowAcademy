<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grow_ad_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->timestamp('activated_at');
            $table->timestamps();
        });

        Schema::create('grow_ad_contents', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->string('event_name', 120);
            $table->text('description');
            $table->string('media_path');
            $table->string('media_type', 10);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('grow_ad_campaigns', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 30)->default('draft');
            $table->unsignedBigInteger('daily_budget')->nullable();
            $table->unsignedInteger('duration_days')->nullable();
            $table->unsignedBigInteger('ad_budget')->nullable();
            $table->unsignedInteger('vat_percent')->nullable();
            $table->unsignedBigInteger('vat_amount')->nullable();
            $table->unsignedBigInteger('service_fee')->nullable();
            $table->unsignedBigInteger('total_amount')->nullable();
            $table->string('payment_status', 20)->default('pending');
            $table->string('midtrans_order_id')->nullable()->unique();
            $table->text('snap_token')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_type')->nullable();
            $table->json('payment_payload')->nullable();
            $table->string('pixel_id', 30)->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('metrics_updated_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        Schema::create('grow_ad_placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('grow_ad_campaigns')->cascadeOnDelete();
            $table->foreignId('content_id')->constrained('grow_ad_contents')->restrictOnDelete();
            // Keep purchased content intact when Admin changes the catalogue.
            $table->string('title', 120);
            $table->string('event_name', 120);
            $table->text('description');
            $table->string('media_path');
            $table->string('media_type', 10);
            $table->string('meta_ad_id', 40)->nullable();
            $table->unsignedBigInteger('impressions')->nullable();
            $table->unsignedBigInteger('clicks')->nullable();
            $table->timestamps();
            $table->unique(['campaign_id', 'content_id']);
        });

        Schema::table('recruitment_leads', function (Blueprint $table) {
            $table->foreignId('grow_ad_placement_id')->nullable()->constrained('grow_ad_placements')->nullOnDelete();
            $table->string('event_payment_status', 20)->default('unconfirmed');
            $table->timestamp('event_paid_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('recruitment_leads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('grow_ad_placement_id');
            $table->dropColumn(['event_payment_status', 'event_paid_at']);
        });
        Schema::dropIfExists('grow_ad_placements');
        Schema::dropIfExists('grow_ad_campaigns');
        Schema::dropIfExists('grow_ad_contents');
        Schema::dropIfExists('grow_ad_settings');
    }
};
