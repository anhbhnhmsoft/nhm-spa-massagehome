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
        Schema::create('coupon_used', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('coupon_id')->comment('ID mã giảm giá');
            $table->unsignedBigInteger('user_id')->comment('ID người dùng sử dụng');
            $table->unsignedBigInteger('service_id')->comment('ID dịch vụ');
            $table->unsignedBigInteger('booking_id')->unique()->comment('ID đơn đặt lịch/giao dịch');

            $table->foreign('coupon_id')->references('id')->on('coupons')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
            $table->foreign('booking_id')->references('id')->on('service_bookings')->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['booking_id', 'coupon_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupon_used');
    }
};
