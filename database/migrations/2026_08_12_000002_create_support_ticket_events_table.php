<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('support_ticket_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('actor_admin_id')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->string('event_type', 40)->index();
            $table->foreignId('from_staff_id')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->foreignId('to_staff_id')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['support_ticket_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_events');
    }
};
