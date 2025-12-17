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
            $table->dropColumn('affiliate_link');
        });
        Schema::table('coupon_users', function (Blueprint $table) {
            $table->boolean('is_used')->default(false);
            $table->dropColumn('quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('affiliate_link');
        });
        Schema::table('coupon_users', function (Blueprint $table) {
            $table->boolean('is_used')->default(false);
            $table->dropColumn('quantity');
        });
    }
};
