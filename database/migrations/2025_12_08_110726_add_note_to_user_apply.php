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
            if (!Schema::hasColumn('user_review_application', 'note')) {
                $table->string('note')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_review_application', function (Blueprint $table) {
            if (Schema::hasColumn('user_review_application', 'note')) {
                $table->dropColumn('note');
            }
        });
    }
};
