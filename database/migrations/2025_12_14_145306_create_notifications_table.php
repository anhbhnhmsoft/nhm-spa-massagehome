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
        // Bảng notifications - Lưu trữ thông tin các thông báo gửi đến người dùng
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('title')->comment('Tiêu đề thông báo');
            $table->text('description')->comment('Nội dung thông báo');
            $table->text('data')->nullable()->comment('Dữ liệu bổ sung (json format)');
            $table->smallInteger('type')->comment('Loại thông báo (trong enum NotificationType)');
            $table->smallInteger('status')->default(0)->comment('Trạng thái thông báo (trong enum NotificationStatus)');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
