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
        Schema::create('ktv_proactive_invites', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ktv_id')->comment('ID KTV gửi lời mời');
            $table->unsignedBigInteger('customer_id')->comment('ID Khách nhận lời mời');
            $table->unsignedBigInteger('request_id')->nullable()->comment('ID Yêu cầu dịch vụ (nếu có)');
            $table->smallInteger('status')->default(1)->comment('Trạng thái lời mời: 1=pending, 2=accepted, 3=declined, 4=expired, 5=canceled_by_admin');
            $table->string('note', 500)->nullable()->comment('Ghi chú/Lời nhắn từ KTV');
            $table->timestamp('expires_at')->comment('Thời điểm hết hạn lời mời');
            $table->timestamps();

            $table->foreign('ktv_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('request_id')->references('id')->on('service_requests')->onDelete('set null');

            $table->index(['ktv_id', 'status']);
            $table->index(['customer_id', 'status']);
        });

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_proactive_matching_enabled')) {
                $table->boolean('is_proactive_matching_enabled')->default(true)->after('status')->comment('Khách bật/tắt nhận đề xuất từ KTV quanh đây');
            }
            if (!Schema::hasColumn('users', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('is_proactive_matching_enabled');
            }
            if (!Schema::hasColumn('users', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ktv_proactive_invites');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_proactive_matching_enabled', 'latitude', 'longitude']);
        });
    }
};
