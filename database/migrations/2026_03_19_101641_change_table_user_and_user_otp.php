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
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->unique()->nullable()->after('phone');
            $table->timestamp('email_verified_at')->nullable()->after('phone_verified_at');
            $table->string('phone')->nullable()->change();
        });
        Schema::table('user_otp', function (Blueprint $table) {
            $table->string('email')->nullable()->after('phone');
            $table->string('phone')->nullable()->change();
            $table->dropIndex('user_otp_phone_type_index');
            $table->index(['phone', 'type']);
            $table->index(['email', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['email', 'email_verified_at']);
            $table->string('phone')->nullable(false)->change();
        });

        Schema::table('user_otp', function (Blueprint $table) {
            $table->dropIndex(['email', 'type']);
            $table->dropColumn('email');
            $table->string('phone')->nullable(false)->change();
            $table->index(['phone', 'type'], 'user_otp_phone_type_index');
        });
    }
};
