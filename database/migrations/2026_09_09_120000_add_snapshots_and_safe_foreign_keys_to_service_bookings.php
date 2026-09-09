<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Thêm các cột snapshot vào bảng service_bookings
        Schema::table('service_bookings', function (Blueprint $table) {
            // Snapshot KTV
            $table->string('ktv_name')->nullable()->after('ktv_user_id')->comment('Snapshot Tên/Nickname KTV tại thời điểm nhận đơn');
            $table->string('ktv_phone', 50)->nullable()->after('ktv_name')->comment('Snapshot Số điện thoại KTV');
            $table->string('ktv_avatar_url', 500)->nullable()->after('ktv_phone')->comment('Snapshot URL ảnh đại diện KTV');

            // Snapshot Khách hàng (Đầy đủ Tên, SĐT, Avatar, Giới tính)
            $table->string('customer_name')->nullable()->after('user_id')->comment('Snapshot Tên khách hàng');
            $table->string('customer_phone', 50)->nullable()->after('customer_name')->comment('Snapshot Số điện thoại khách hàng');
            $table->string('customer_avatar_url', 500)->nullable()->after('customer_phone')->comment('Snapshot URL ảnh đại diện khách hàng');
            $table->smallInteger('customer_gender')->nullable()->after('customer_avatar_url')->comment('Snapshot Giới tính khách hàng (1: Nam, 2: Nữ)');

            // Snapshot Dịch vụ
            $table->string('service_name')->nullable()->after('category_id')->comment('Snapshot Tên dịch vụ');
        });

        // 2. Chuyển các khóa ngoại từ CASCADE sang SET NULL để chống mất đơn khi xóa User/KTV/Dịch vụ
        DB::statement('ALTER TABLE service_bookings DROP CONSTRAINT IF EXISTS service_bookings_ktv_user_id_foreign');
        DB::statement('ALTER TABLE service_bookings DROP CONSTRAINT IF EXISTS service_bookings_user_id_foreign');
        DB::statement('ALTER TABLE service_bookings DROP CONSTRAINT IF EXISTS service_bookings_category_id_foreign');

        // Cho phép các cột này nhận giá trị NULL
        DB::statement('ALTER TABLE service_bookings ALTER COLUMN ktv_user_id DROP NOT NULL');
        DB::statement('ALTER TABLE service_bookings ALTER COLUMN user_id DROP NOT NULL');
        DB::statement('ALTER TABLE service_bookings ALTER COLUMN category_id DROP NOT NULL');

        // Tạo lại khóa ngoại với ON DELETE SET NULL
        DB::statement('ALTER TABLE service_bookings ADD CONSTRAINT service_bookings_ktv_user_id_foreign FOREIGN KEY (ktv_user_id) REFERENCES users(id) ON DELETE SET NULL');
        DB::statement('ALTER TABLE service_bookings ADD CONSTRAINT service_bookings_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL');
        DB::statement('ALTER TABLE service_bookings ADD CONSTRAINT service_bookings_category_id_foreign FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL');

        // 3. Tự động Backfill dữ liệu snapshot cho các đơn hàng hiện có
        // Đồng bộ snapshot thông tin KTV
        DB::statement("
            UPDATE service_bookings sb
            SET 
                ktv_name = COALESCE(ura.nickname, u.name),
                ktv_phone = u.phone,
                ktv_avatar_url = up.avatar_url
            FROM users u
            LEFT JOIN user_review_application ura ON ura.user_id = u.id
            LEFT JOIN user_profiles up ON up.user_id = u.id
            WHERE sb.ktv_user_id = u.id
        ");

        // Đồng bộ snapshot thông tin Khách hàng (Tên, SĐT, Avatar, Giới tính)
        DB::statement("
            UPDATE service_bookings sb
            SET 
                customer_name = u.name,
                customer_phone = u.phone,
                customer_avatar_url = up.avatar_url,
                customer_gender = up.gender
            FROM users u
            LEFT JOIN user_profiles up ON up.user_id = u.id
            WHERE sb.user_id = u.id
        ");

        // Đồng bộ snapshot Tên dịch vụ
        DB::statement("
            UPDATE service_bookings sb
            SET service_name = c.name
            FROM categories c
            WHERE sb.category_id = c.id
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_bookings', function (Blueprint $table) {
            $table->dropColumn([
                'ktv_name',
                'ktv_phone',
                'ktv_avatar_url',
                'customer_name',
                'customer_phone',
                'customer_avatar_url',
                'customer_gender',
                'service_name',
            ]);
        });
    }
};
