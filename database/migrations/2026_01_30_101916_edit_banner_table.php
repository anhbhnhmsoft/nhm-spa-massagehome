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
        // Thêm cột type vào bảng banners
        Schema::table('banners', function (Blueprint $table) {
            $table->unsignedSmallInteger('type')->nullable()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Xóa cột type khỏi bảng banners
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
