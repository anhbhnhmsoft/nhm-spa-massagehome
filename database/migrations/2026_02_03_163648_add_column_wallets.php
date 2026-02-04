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
        // Thêm cột frozen_balance vào bảng wallets
        Schema::table('wallets', function (Blueprint $table) {
            $table->decimal('frozen_balance', 15, 2)
                ->default(0.00)
                ->comment('số dư đóng băng')
                ->after('balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Xóa cột frozen_balance khỏi bảng wallets
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn('frozen_balance');
        });
    }
};
