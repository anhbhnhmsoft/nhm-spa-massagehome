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
        Schema::table('affiliate_links', function (Blueprint $table) {
            $table->unsignedBigInteger('referrer_id')->index()->comment('ID người giới thiệu');
            $table->foreign('referrer_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('reffered_user_id')->index()->comment('ID người giới thiệu');
            $table->foreign('reffered_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('affiliate_links', function (Blueprint $table) {
            $table->dropForeign(['referrer_id']);
            $table->dropForeign(['reffered_user_id']);
            $table->dropColumn('referrer_id');
            $table->dropColumn('reffered_user_id');
            $table->unsignedBigInteger('user_id')->index()->comment('ID người giới thiệu');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
