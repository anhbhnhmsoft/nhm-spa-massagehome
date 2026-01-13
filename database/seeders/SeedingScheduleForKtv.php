<?php

namespace Database\Seeders;

use App\Enums\KTVConfigSchedules;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class SeedingScheduleForKtv extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::where('role', UserRole::KTV->value)
            ->get()->each(function ($user) {
                $user->schedule()->updateOrCreate([
                    'ktv_id' => $user->id,
                ], [
                    'is_working' => true,
                    'working_schedule' => KTVConfigSchedules::getDefaultSchema(),
                ]);
        });
    }
}
