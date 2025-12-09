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
        Schema::create('agency_ktv', function (Blueprint $table) {
            $table->comment('Bảng agency_ktv lưu trữ thông tin về các KTV có thuộc quản lý bởi một Agency.');
            $table->id();
            $table->unsignedBigInteger('agency_id');
            $table->foreign('agency_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('ktv_id');
            $table->foreign('ktv_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamp('join_at')->nullable()->comment('thời gian gia nhập Agency');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agency_ktv');
    }
};
