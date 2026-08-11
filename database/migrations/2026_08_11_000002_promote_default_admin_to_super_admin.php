<?php

use App\Enums\Admin\AdminRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // The seeded system account is the bootstrap superadmin on existing databases too.
        DB::table('admin_users')
            ->where('username', '012345678910')
            ->update(['role' => AdminRole::SUPER_ADMIN->value]);
    }

    public function down(): void
    {
        DB::table('admin_users')
            ->where('username', '012345678910')
            ->where('role', AdminRole::SUPER_ADMIN->value)
            ->update(['role' => AdminRole::PROFILE_MANAGER->value]);
    }
};
