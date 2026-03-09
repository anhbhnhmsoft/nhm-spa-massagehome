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
        Schema::table('user_review_application', function (Blueprint $table) {
            $table->string('address')->nullable()->comment('địa chỉ (dành cho Đối tác)');
            $table->decimal('latitude')->nullable()->comment('vĩ độ (dành cho Đối tác)');
            $table->decimal('longitude')->nullable()->comment('kinh độ (dành cho Đối tác)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
