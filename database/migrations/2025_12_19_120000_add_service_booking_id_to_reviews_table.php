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
        Schema::table('reviews', function (Blueprint $table) {
            $table->foreignId('service_booking_id')
                ->nullable()
                ->after('review_by')
                ->constrained('service_bookings')
                ->onDelete('cascade')
                ->comment('ID booking dịch vụ được đánh giá');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['service_booking_id']);
            $table->dropColumn('service_booking_id');
        });
    }
};

