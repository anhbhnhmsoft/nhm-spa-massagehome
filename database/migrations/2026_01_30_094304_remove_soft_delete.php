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
            $table->dropSoftDeletes();
        });

        Schema::table('user_devices', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('user_review_application', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('user_files', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('user_address', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('wallets', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('user_withdraw_info', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('affiliate_configs', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('affiliate_links', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('service_bookings', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('coupon_used', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('coupon_users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('configs', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('mobile_notifications', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('static_contract', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
