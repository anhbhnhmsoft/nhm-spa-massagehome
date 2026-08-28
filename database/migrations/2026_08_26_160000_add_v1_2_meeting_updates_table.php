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
        // 1. Thêm các cột vào bảng service_requests
        Schema::table('service_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('service_requests', 'ward')) {
                $table->string('ward', 100)->nullable()->after('address')->comment('Phường/Xã khách hàng');
            }
            if (!Schema::hasColumn('service_requests', 'province')) {
                $table->string('province', 100)->nullable()->after('ward')->comment('Tỉnh/Thành phố khách hàng');
            }
            if (!Schema::hasColumn('service_requests', 'cskh_note')) {
                $table->text('cskh_note')->nullable()->after('note')->comment('Ghi chú dành cho CSKH');
            }
        });

        // 2. Thêm cột cskh_note vào bảng customer_crm_data
        Schema::table('customer_crm_data', function (Blueprint $table) {
            if (!Schema::hasColumn('customer_crm_data', 'cskh_note')) {
                $table->text('cskh_note')->nullable()->comment('Ghi chú CSKH quản lý khách');
            }
        });

        // 3. Thêm các cột vào bảng users
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'ward')) {
                $table->string('ward', 100)->nullable()->comment('Phường/Xã của user/khách');
            }
            if (!Schema::hasColumn('users', 'province')) {
                $table->string('province', 100)->nullable()->comment('Tỉnh/Thành phố của user/khách');
            }
            if (!Schema::hasColumn('users', 'work_province')) {
                $table->string('work_province', 100)->nullable()->comment('Tỉnh/Thành phố KTV đăng ký hoạt động');
            }
            if (!Schema::hasColumn('users', 'work_wards')) {
                $table->json('work_wards')->nullable()->comment('Danh sách Phường/Xã KTV đăng ký hoạt động');
            }
            if (!Schema::hasColumn('users', 'is_online')) {
                $table->boolean('is_online')->default(true)->comment('Trạng thái ON/OFF nhận đơn của KTV');
            }
        });

        // 4. Thêm các cột vào user_review_application
        Schema::table('user_review_application', function (Blueprint $table) {
            if (!Schema::hasColumn('user_review_application', 'certificates')) {
                $table->json('certificates')->nullable()->comment('Danh sách ảnh/file chứng chỉ chuyên môn');
            }
            if (!Schema::hasColumn('user_review_application', 'work_province')) {
                $table->string('work_province', 100)->nullable()->comment('Tỉnh/Thành phố KTV đăng ký khi nộp hồ sơ');
            }
            if (!Schema::hasColumn('user_review_application', 'work_wards')) {
                $table->json('work_wards')->nullable()->comment('Danh sách Phường/Xã KTV đăng ký khi nộp hồ sơ');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropColumn(['ward', 'province', 'cskh_note']);
        });

        Schema::table('customer_crm_data', function (Blueprint $table) {
            $table->dropColumn(['cskh_note']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['ward', 'province', 'work_province', 'work_wards', 'is_online']);
        });

        Schema::table('user_review_application', function (Blueprint $table) {
            $table->dropColumn(['certificates', 'work_province', 'work_wards']);
        });
    }
};
