<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('service_options');

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('name');
            $table->dropColumn('description');
            $table->dropColumn('is_active');
            $table->dropColumn('image_url');
        });

        Schema::table('service_bookings', function (Blueprint $table) {
            // Xóa ràng buộc cũ (phải đúng tên constraint trong lỗi của bạn)
            $table->dropForeign('service_bookings_ktv_user_id_foreign');

            // Tạo lại ràng buộc với cascade
            $table->foreign('ktv_user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        Schema::table('service_bookings', function (Blueprint $table) {
            // 1. Xóa khóa ngoại và cột service_id cũ
            $table->dropForeign('service_bookings_service_id_foreign');
            $table->dropColumn('service_id');

            // 2. Thêm cột category_id mới
            $table->foreignId('category_id')
                ->after('user_id')
                ->constrained('categories')
                ->onDelete('cascade');
        });
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropForeign(['for_service_id']);
            $table->dropColumn('for_service_id');
        });

        Schema::table('coupon_used', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
            $table->dropColumn('service_id');
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
