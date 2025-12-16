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
        Schema::table('coupons', function (Blueprint $table) {
            $table->json('banners')->nullable()->comment('Danh sách banner đa ngôn ngữ');
            $table->boolean('display_ads')->default(true)->comment('Hiển thị quảng cáo ở homepage');
        });

        Schema::create('coupon_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('coupon_id');
            $table->unsignedBigInteger('user_id');
            $table->foreign('coupon_id')->references('id')->on('coupons')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->smallInteger('quantity')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['user_id', 'coupon_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn('banners');
            $table->dropColumn('display_ads');
        });

        Schema::dropIfExists('coupon_users');
    }
};
