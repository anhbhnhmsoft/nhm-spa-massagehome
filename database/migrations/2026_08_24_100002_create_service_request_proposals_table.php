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
        Schema::create('service_request_proposals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('request_id');
            $table->unsignedBigInteger('ktv_id');
            $table->unsignedBigInteger('cskh_id')->nullable();
            $table->smallInteger('status')->default(1); // ProposalStatus::PROPOSED = 1
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('request_id')->references('id')->on('service_requests')->onDelete('cascade');
            $table->foreign('ktv_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('cskh_id')->references('id')->on('admin_users')->onDelete('set null');

            $table->index(['request_id', 'ktv_id']);
            $table->index(['ktv_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_request_proposals');
    }
};
