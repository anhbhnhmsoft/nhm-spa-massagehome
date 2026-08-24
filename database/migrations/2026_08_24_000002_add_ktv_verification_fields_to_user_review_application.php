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
            $table->string('contact_phone', 20)->nullable()->comment('SĐT liên hệ phục vụ điều phối đơn khẩn cấp');
            $table->boolean('contact_verified')->default(false)->comment('Trạng thái đã xác thực liên lạc được');

            $table->boolean('portrait_verified')->default(false)->comment('Đã xác thực chân dung bởi MasaHome');
            $table->timestamp('portrait_verified_at')->nullable()->comment('Thời gian xác thực chân dung');

            $table->boolean('certificate_verified')->default(false)->comment('Trạng thái đã xác thực chứng chỉ/bằng cấp');
            $table->json('certificates')->nullable()->comment('Danh sách chứng chỉ / bằng cấp chuyên môn');

            $table->json('techniques')->nullable()->comment('Danh sách mã/enum kỹ thuật chuyên môn KTV');
            $table->json('strength_service_ids')->nullable()->comment('Danh sách ID các dịch vụ thế mạnh (tối đa 3)');

            $table->string('district_code', 20)->nullable()->comment('Mã Quận/Huyện hoạt động');
            $table->string('ward_code', 20)->nullable()->comment('Mã Xã/Phường hoạt động');
            $table->json('priority_areas')->nullable()->comment('Danh sách các khu vực ưu tiên phục vụ');
            $table->json('service_locations')->nullable()->comment('Danh sách địa điểm phục vụ (1: Nhà riêng, 2: Khách sạn)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_review_application', function (Blueprint $table) {
            $table->dropColumn([
                'contact_phone',
                'contact_verified',
                'portrait_verified',
                'portrait_verified_at',
                'certificate_verified',
                'certificates',
                'techniques',
                'strength_service_ids',
                'district_code',
                'ward_code',
                'priority_areas',
                'service_locations',
            ]);
        });
    }
};
