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
        DB::table('customer_crm_data')->where('customer_rank', 'standard')->update(['customer_rank' => 1]);
        DB::table('customer_crm_data')->where('customer_rank', 'gold')->update(['customer_rank' => 2]);
        DB::table('customer_crm_data')->where('customer_rank', 'vip')->update(['customer_rank' => 3]);

        DB::table('customer_crm_data')->where('demand_status', 'need_now')->update(['demand_status' => 1]);
        DB::table('customer_crm_data')->where('demand_status', 'exploring')->update(['demand_status' => 2]);
        DB::table('customer_crm_data')->where('demand_status', 'booked')->update(['demand_status' => 3]);
        DB::table('customer_crm_data')->where('demand_status', 'no_need')->update(['demand_status' => 4]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('customer_crm_data')->where('customer_rank', 1)->update(['customer_rank' => 'standard']);
        DB::table('customer_crm_data')->where('customer_rank', 2)->update(['customer_rank' => 'gold']);
        DB::table('customer_rank')->where('customer_rank', 3)->update(['customer_rank' => 'vip']);

        DB::table('customer_crm_data')->where('demand_status', 1)->update(['demand_status' => 'need_now']);
        DB::table('customer_crm_data')->where('demand_status', 2)->update(['demand_status' => 'exploring']);
        DB::table('customer_crm_data')->where('demand_status', 3)->update(['demand_status' => 'booked']);
        DB::table('customer_crm_data')->where('demand_status', 4)->update(['demand_status' => 'no_need']);
    }
};
