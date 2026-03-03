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
            $table->renameColumn('price_before_discount','price_discount');
            $table->dropColumn('note_address');
            $table->string('ktv_address', 255)->nullable();
            $table->decimal('ktv_latitude', 10, 8)->nullable();
            $table->decimal('ktv_longitude', 11, 8)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_bookings', function (Blueprint $table) {
            $table->renameColumn('price_discount','price_before_discount');
            $table->string('note_address', 255)->nullable();
            $table->dropColumn('ktv_address');
            $table->dropColumn('ktv_latitude');
            $table->dropColumn('ktv_longitude');
        });
    }
};
