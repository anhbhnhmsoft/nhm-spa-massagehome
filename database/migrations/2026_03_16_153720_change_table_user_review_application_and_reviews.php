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
            $table->unsignedBigInteger('service_booking_id')->nullable()->change();
            $table->unsignedBigInteger('review_by')->nullable()->change();
            $table->boolean('is_virtual')->default(false);
            $table->string('virtual_name')->nullable();
        });

        Schema::table('services', function (Blueprint $table) {
            $table->unsignedInteger('performed_count')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            // Quay trở lại trạng thái ban đầu nếu cần rollback
            // Lưu ý: Chỉ rollback được nếu toàn bộ dữ liệu hiện tại không có dòng nào đang NULL
            $table->unsignedBigInteger('service_booking_id')->nullable(false)->change();
            $table->unsignedBigInteger('review_by')->nullable(false)->change();
            $table->dropColumn('is_virtual');
            $table->dropColumn('virtual_name');
        });
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('performed_count');
        });
    }
};
