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
        Schema::create('user_otp', function (Blueprint $table) {
            $table->id();
            $table->string('phone');
            $table->string('otp_hash');
            $table->smallInteger('type');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expired_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->unsignedTinyInteger('send_count')->default(1);
            $table->string('ip_address')->nullable();
            $table->timestamps();
            $table->index(['phone', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('user_otp');
    }
};
