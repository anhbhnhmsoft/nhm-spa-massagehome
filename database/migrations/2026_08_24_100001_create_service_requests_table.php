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
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('cskh_id')->nullable();
            $table->unsignedBigInteger('service_id');
            $table->json('preferred_techniques')->nullable();
            $table->string('province_code')->nullable();
            $table->string('district_code')->nullable();
            $table->string('ward_code')->nullable();
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->date('preferred_date')->nullable();
            $table->string('time_slot')->nullable();
            $table->smallInteger('urgency_level')->default(1); // UrgencyLevel::NEED_NOW = 1
            $table->json('preferred_ktv_ids')->nullable();
            $table->text('note')->nullable();
            $table->smallInteger('status')->default(1); // ServiceRequestStatus::NEW = 1
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('customer_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('cskh_id')->references('id')->on('admin_users')->onDelete('set null');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');

            $table->index(['customer_id', 'status']);
            $table->index(['province_code', 'district_code', 'ward_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
