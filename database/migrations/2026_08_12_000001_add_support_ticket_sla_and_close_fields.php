<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->timestamp('assigned_at')->nullable()->index();
            $table->timestamp('first_response_at')->nullable()->index();
            $table->timestamp('closed_at')->nullable()->index();
            $table->foreignId('closed_by_admin_id')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->string('close_reason', 80)->nullable();
            $table->text('close_note')->nullable();
            $table->timestamp('sla_warning_at')->nullable()->index();
            $table->timestamp('sla_breached_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropForeign(['closed_by_admin_id']);
            $table->dropColumn([
                'assigned_at', 'first_response_at', 'closed_at', 'closed_by_admin_id',
                'close_reason', 'close_note', 'sla_warning_at', 'sla_breached_at',
            ]);
        });
    }
};
