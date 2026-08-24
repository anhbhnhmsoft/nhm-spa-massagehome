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
            DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');
        } catch (\Throwable $e) {
            // PostGIS package optional / skipped if not installed in PostgreSQL system
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            DB::statement('DROP EXTENSION IF EXISTS postgis');
        } catch (\Throwable $e) {
            // Ignore if postgis not present
        }
    }
};
