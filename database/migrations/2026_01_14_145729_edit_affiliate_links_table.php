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
            $table->renameColumn('reffered_user_id', 'referred_user_id');
            $table->string('user_agent', 500)->nullable()->change();
            $table->bigInteger('referred_user_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('affiliate_links', function (Blueprint $table) {
            $table->renameColumn('referred_user_id', 'reffered_user_id');
            $table->text('user_agent')->nullable(false)->change();
            $table->bigInteger('reffered_user_id')->nullable(false)->change();
        });
    }
};
