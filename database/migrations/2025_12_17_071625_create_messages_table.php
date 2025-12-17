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
        // Bảng messages - Lưu nội dung tin nhắn trong phòng chat
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('room_id')->comment('ID phòng chat (chat_rooms.id)');
            $table->bigInteger('sender_by')->comment('ID người gửi (users.id)');
            $table->text('content')->comment('Nội dung tin nhắn');
            $table->timestamps();

            $table->foreign('room_id')->references('id')->on('chat_rooms')->onDelete('cascade');
            $table->foreign('sender_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};


