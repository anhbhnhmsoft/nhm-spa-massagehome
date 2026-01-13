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
        Schema::table('service_bookings', function (Blueprint $table) {
            $table->unsignedBigInteger('ktv_user_id')->comment('id người làm dịch vụ (KTV)');
            $table->foreign('ktv_user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_bookings', function (Blueprint $table) {
            $table->dropForeign('service_bookings_ktv_user_id_foreign');
            $table->dropColumn('ktv_user_id');
        });
    }
};
