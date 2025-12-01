<?php

namespace Database\Seeders;

use App\Enums\Language;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->create([
            'phone' => '012345678910',
            'phone_verified_at' => now(),
            'password' => bcrypt('Test1234568@'),
            'name' => 'Admin System',
            'role' => UserRole::ADMIN->value,
            'language' => Language::VIETNAMESE->value,
            'is_active' => true,
            'referral_code' => 'ADMIN001',
        ]);
    }
}
