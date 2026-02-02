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
            // 1. Đảm bảo cột có thể chứa giá trị null
            $table->bigInteger('referrer_id')->nullable()->change();

            // 2. Xóa bỏ constraint cũ (dựa trên tên bạn cung cấp)
            $table->dropForeign('user_review_application_agency_id_foreign');

            // 3. Tạo constraint mới với ON DELETE SET NULL
            $table->foreign('referrer_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_review_application', function (Blueprint $table) {
            $table->dropForeign(['referrer_id']);

            $table->foreign('referrer_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }
};
