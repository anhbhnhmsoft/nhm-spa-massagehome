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
        //
        Schema::table('user_address', function (Blueprint $table) {
            $table->string('address', 500)->nullable()->comment('địa chỉ chi tiết')->change();
            $table->index(['user_id', 'is_primary']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('user_address', function (Blueprint $table) {
            $table->string('address', 255)->nullable()->comment('địa chỉ chi tiết')->change();
            $table->dropIndex(['user_id', 'is_primary']);
        });
    }
};
