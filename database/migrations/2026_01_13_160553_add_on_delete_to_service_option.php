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
        Schema::table('service_options', function (Blueprint $table) {
            $table->dropForeign(['category_price_id']);

            $table->foreign('category_price_id')
                ->references('id')
                ->on('category_prices')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_options', function (Blueprint $table) {
            $table->dropForeign(['category_price_id']);
            $table->foreign('category_price_id')
                ->references('id')
                ->on('category_prices');
        });
    }
};
