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
        Schema::create('affiliate_links', function (Blueprint $table) {
            $table->comment('Bảng lưu trữ thông tin nhấp chuột (fingerprinting) cho affiliate');

            $table->bigInteger('id')->primary();

            $table->bigInteger('referrer_id')->index()->comment('ID của người giới thiệu (User)');

            $table->bigInteger('referred_user_id')->nullable()->comment('ID của người được giới thiệu (User mới)');

            $table->string('client_ip')->comment('Địa chỉ IP của người nhấp chuột (lúc click)');
            $table->text('user_agent')->comment('Thông tin thiết bị/trình duyệt (lúc click)');

            $table->boolean('is_matched')->default(false)->comment('Đã đối sánh/chuyển đổi thành công chưa');

            $table->timestamp('expired_at')->comment('Thời gian hết hạn đối sánh (Fingerprinting)');

            $table->softDeletes();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliate_links');
    }
};
