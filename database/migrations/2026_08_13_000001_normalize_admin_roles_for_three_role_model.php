<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Legacy marketing/profile values now belong to the single ADMIN role.
        DB::table('admin_users')
            ->whereIn('role', [2, 3])
            ->update(['role' => 1]);
    }

    public function down(): void
    {
        // The old role split cannot be reconstructed from the normalized value.
        // Keep normalized administrators as the legacy value 1.
    }
};
