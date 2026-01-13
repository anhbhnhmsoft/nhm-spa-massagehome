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
        Schema::create('user_ktv_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ktv_id')
                ->unique()
                ->constrained('users')
                ->onDelete('cascade');
            $table->jsonb('working_schedule')->comment('Lưu lịch làm việc từ thứ 2 đến CN');
            // Nút bật/tắt làm việc hằng ngày
            $table->boolean('is_working')->default(false)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_ktv_schedules');
    }
};
