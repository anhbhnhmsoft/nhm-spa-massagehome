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
        // Bảng chat_rooms - Lưu thông tin phòng chat giữa khách hàng và KTV
        Schema::create('chat_rooms', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('customer_id')->comment('ID khách hàng');
            $table->bigInteger('ktv_id')->comment('ID KTV');
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('ktv_id')->references('id')->on('users')->onDelete('cascade');

            $table->index(['customer_id', 'ktv_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_rooms');
    }
};


