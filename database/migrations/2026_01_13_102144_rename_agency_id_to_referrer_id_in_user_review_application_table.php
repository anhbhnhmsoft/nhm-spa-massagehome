<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('user_review_application', function (Blueprint $blueprint) {
            $blueprint->renameColumn('agency_id', 'referrer_id');
        });
    }

    public function down(): void
    {
        Schema::table('user_review_application', function (Blueprint $blueprint) {
            // Đảo ngược lại nếu cần rollback
            $blueprint->renameColumn('referrer_id', 'agency_id');
        });
    }
};
