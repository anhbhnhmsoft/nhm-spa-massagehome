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
        Schema::create('user_address', function (Blueprint $table) {
            $table->id();
            $table->comment('Bảng lưu danh sách địa chỉ của người dùng');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('address', 255);
            $table->decimal('latitude', 10, 8)->default(0);
            $table->decimal('longitude', 11, 8)->default(0);
            $table->text('desc')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_address');
    }
};
