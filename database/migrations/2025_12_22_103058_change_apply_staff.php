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
            $table->smallInteger('role')->nullable()->comment('Vai trò muốn apply (trong enum UserRole)');
        });

        Schema::table('user_files', function (Blueprint $table) {
            $table->smallInteger('role')->nullable()->comment('Vai trò muốn apply (trong enum UserRole)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_review_application', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        Schema::table('user_files', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
