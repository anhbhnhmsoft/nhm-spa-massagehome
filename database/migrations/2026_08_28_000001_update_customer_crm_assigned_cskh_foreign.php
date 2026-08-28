<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customer_crm_data', function (Blueprint $table) {
            DB::statement('ALTER TABLE customer_crm_data DROP CONSTRAINT IF EXISTS customer_crm_data_assigned_cskh_id_foreign');

            $table->foreign('assigned_cskh_id')
                ->references('id')
                ->on('admin_users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_crm_data', function (Blueprint $table) {
            DB::statement('ALTER TABLE customer_crm_data DROP CONSTRAINT IF EXISTS customer_crm_data_assigned_cskh_id_foreign');

            $table->foreign('assigned_cskh_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }
};
