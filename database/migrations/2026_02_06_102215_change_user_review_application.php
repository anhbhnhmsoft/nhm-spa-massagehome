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
        Schema::table('user_review_application', function (Blueprint $table) {
            // drop columns
            $table->dropColumn([
                'province_code',
                'address',
                'latitude',
                'longitude',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_review_application', function (Blueprint $table) {
            // add columns
            $table->string('province_code')->nullable()->after('user_id');
            $table->string('address', 255)->nullable()->after('province_code');
            $table->decimal('latitude', 10, 8)->nullable()->after('address');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
        });
    }
};
