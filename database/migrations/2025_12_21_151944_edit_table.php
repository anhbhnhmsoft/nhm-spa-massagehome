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
        // Thêm cột seen_at vào bảng messages
        Schema::table('messages', function (Blueprint $table) {
            $table->timestamp('seen_at')->nullable()->after('content')->comment('Thời gian đã đọc tin nhắn');
            $table->string('temp_id')->nullable()->after('id')->comment('ID tạm thời cho tin nhắn');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Xóa cột seen_at khỏi bảng messages
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('seen_at');
            $table->dropColumn('temp_id');
        });
    }
};
