<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Preserve the closest existing responsibility while moving to the new role model:
        // ADMIN -> PROFILE_MANAGER, ACCOUNTANT -> OPERATION_MANAGER, EMPLOYEE -> CUSTOMER_SUPPORT.
        DB::statement("UPDATE admin_users SET role = CASE role WHEN 1 THEN 3 WHEN 2 THEN 1 WHEN 3 THEN 4 ELSE role END");
    }

    public function down(): void
    {
        // The old role model had no marketing/profile/customer-support equivalents.
        DB::statement("UPDATE admin_users SET role = CASE role WHEN 1 THEN 2 WHEN 3 THEN 1 WHEN 4 THEN 3 ELSE role END");
    }
};
