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
        try {
            DB::statement('ALTER TABLE customer_crm_data ALTER COLUMN demand_status TYPE integer USING demand_status::integer');
        } catch (\Throwable $e) {}

        try {
            DB::statement('ALTER TABLE customer_crm_data ALTER COLUMN customer_rank TYPE integer USING customer_rank::integer');
        } catch (\Throwable $e) {}

        try {
            DB::statement('ALTER TABLE customer_crm_data ALTER COLUMN demand_status DROP NOT NULL');
            DB::statement("ALTER TABLE customer_crm_data ALTER COLUMN demand_status SET DEFAULT 2");
        } catch (\Throwable $e) {}

        try {
            DB::statement('ALTER TABLE customer_crm_data ALTER COLUMN customer_rank DROP NOT NULL');
            DB::statement("ALTER TABLE customer_crm_data ALTER COLUMN customer_rank SET DEFAULT 1");
        } catch (\Throwable $e) {}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
