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
        Schema::table('users', function (Blueprint $table) {
            // ERD fields
            $table->string('full_name')->nullable()->after('id'); // kalau kamu mau beda dari name
            $table->string('status')->nullable()->after('full_name'); // aktif/nonaktif/...
            $table->string('dst_code')->nullable()->after('status');

            $table->date('date_of_birth')->nullable()->after('dst_code');
            $table->string('phone_number')->nullable()->after('date_of_birth');
            $table->date('join_date')->nullable()->after('phone_number');

            $table->string('city_of_domicile')->nullable()->after('join_date');

            // simpan path file (storage)
            $table->string('photo')->nullable()->after('city_of_domicile');
            $table->string('id_card')->nullable()->after('photo');

            $table->timestamp('last_login_at')->nullable()->after('remember_token');

            // index yang berguna
            $table->index('phone_number');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['phone_number']);
            $table->dropIndex(['status']);

            $table->dropColumn([
                'full_name',
                'status',
                'dst_code',
                'date_of_birth',
                'phone_number',
                'join_date',
                'city_of_domicile',
                'photo',
                'id_card',
                'last_login_at',
            ]);
        });
    }
};
