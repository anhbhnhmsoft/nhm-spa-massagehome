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
        if (!Schema::hasTable('customer_crm_data')) {
            Schema::create('customer_crm_data', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->primary();
                $table->json('languages')->nullable();
                $table->unsignedBigInteger('province_id')->nullable();
                $table->unsignedBigInteger('district_id')->nullable();
                $table->unsignedBigInteger('ward_id')->nullable();
                $table->string('address_detail')->nullable();
                $table->json('preferred_services')->nullable();
                $table->json('preferred_techniques')->nullable();
                $table->json('preferred_time_slots')->nullable();
                $table->string('demand_status')->default('2');
                $table->decimal('total_spent', 15, 2)->default(0);
                $table->integer('booking_count')->default(0);
                $table->decimal('aov', 15, 2)->default(0);
                $table->timestamp('first_booking_at')->nullable();
                $table->timestamp('last_booking_at')->nullable();
                $table->json('favorite_ktv_ids')->nullable();
                $table->json('frequent_booking_hours')->nullable();
                $table->unsignedBigInteger('assigned_cskh_id')->nullable();
                $table->string('customer_rank')->default('1');
                $table->text('cskh_notes')->nullable();
                $table->text('cskh_note')->nullable();
                $table->softDeletes();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_crm_data');
    }
};
