<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Chuẩn hóa toàn bộ dữ liệu string sang integer trong customer_crm_data
        DB::table('customer_crm_data')->where('customer_rank', 'standard')->update(['customer_rank' => '1']);
        DB::table('customer_crm_data')->where('customer_rank', 'gold')->update(['customer_rank' => '2']);
        DB::table('customer_crm_data')->where('customer_rank', 'vip')->update(['customer_rank' => '3']);

        DB::table('customer_crm_data')->where('demand_status', 'need_now')->update(['demand_status' => '1']);
        DB::table('customer_crm_data')->where('demand_status', 'exploring')->update(['demand_status' => '2']);
        DB::table('customer_crm_data')->where('demand_status', 'booked')->update(['demand_status' => '3']);
        DB::table('customer_crm_data')->where('demand_status', 'no_need')->update(['demand_status' => '4']);

        // 2. Thay đổi Default value trong Database
        DB::statement("ALTER TABLE customer_crm_data ALTER COLUMN customer_rank SET DEFAULT '1'");
        DB::statement("ALTER TABLE customer_crm_data ALTER COLUMN demand_status SET DEFAULT '2'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
