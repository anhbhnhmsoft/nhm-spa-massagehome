<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_bookings', function (Blueprint $table) {
            $table->unsignedBigInteger('original_ktv_user_id')->nullable()->after('ktv_user_id');
            $table->timestamp('ktv_confirm_deadline_at')->nullable()->after('cancel_by');
            $table->timestamp('application_opened_at')->nullable()->after('ktv_confirm_deadline_at');
            $table->string('application_open_reason', 100)->nullable()->after('application_opened_at');

            $table->foreign('original_ktv_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index(['status', 'application_opened_at']);
            $table->index('ktv_confirm_deadline_at');
        });

        Schema::create('booking_applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('ktv_id');
            $table->smallInteger('status')->comment('BookingApplicationStatus enum');
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('selected_at')->nullable();
            $table->string('removed_reason', 255)->nullable();
            $table->timestamps();

            $table->foreign('booking_id')
                ->references('id')
                ->on('service_bookings')
                ->cascadeOnDelete();
            $table->foreign('ktv_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->unique(['booking_id', 'ktv_id']);
            $table->index(['booking_id', 'status']);
            $table->index(['ktv_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_applications');

        Schema::table('service_bookings', function (Blueprint $table) {
            $table->dropForeign(['original_ktv_user_id']);
            $table->dropIndex(['status', 'application_opened_at']);
            $table->dropIndex(['ktv_confirm_deadline_at']);
            $table->dropColumn([
                'original_ktv_user_id',
                'ktv_confirm_deadline_at',
                'application_opened_at',
                'application_open_reason',
            ]);
        });
    }
};
