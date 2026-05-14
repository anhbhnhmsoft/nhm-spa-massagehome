<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('support_categories')->restrictOnDelete();
            $table->foreignId('assigned_staff_id')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->foreignId('latest_booking_id')->nullable()->constrained('service_bookings')->nullOnDelete();
            $table->string('room_id')->nullable()->unique();
            $table->smallInteger('status')->default(0);
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
