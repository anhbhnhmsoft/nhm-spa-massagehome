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
        Schema::create('category_prices', function (Blueprint $table) {
            $table->comment('Bảng lưu trữ giá của từng loại dịch vụ');
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->foreign('category_id')->references('id')->on('categories');
            $table->decimal('price', 15, 2)->comment('Giá dịch vụ');
            $table->smallInteger('duration')->comment('Thời gian dịch vụ (phút)');
            $table->timestamps();
        });

        Schema::table('service_options', function (Blueprint $table) {
            $table->unsignedBigInteger('category_price_id')->nullable()->comment('Id tùy chọn danh mục');
            $table->foreign('category_price_id')->references('id')->on('category_prices');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_prices');
        Schema::table('service_options', function (Blueprint $table) {
            $table->dropForeign(['category_price_id']);
            $table->dropColumn('category_price_id');
        });
    }
};
