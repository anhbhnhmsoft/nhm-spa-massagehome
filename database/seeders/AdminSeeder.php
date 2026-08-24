<?php

namespace Database\Seeders;

use App\Enums\Admin\AdminRole;
use App\Enums\Language;
use App\Models\AdminUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        AdminUser::query()->createOrFirst(
            [
                'username' => '012345678910',
            ],
            [
                'password' => Hash::make('Test12345678@'),
                'name' => 'Admin System',
                'role' => AdminRole::SUPER_ADMIN,
                'language' => Language::VIETNAMESE->value,
                'is_active' => true,
            ]);

        AdminUser::query()->createOrFirst(
            [
                'username' => 'cskh01',
            ],
            [
                'password' => Hash::make('Test12345678@'),
                'name' => 'Nhân viên CSKH 01',
                'role' => AdminRole::CUSTOMER_SUPPORT,
                'language' => Language::VIETNAMESE->value,
                'is_active' => true,
            ]);

        AdminUser::query()->createOrFirst(
            [
                'username' => 'sale-user',
            ],
            [
                'password' => Hash::make('Test12345678@'),
                'name' => 'Nhân viên Sale',
                'role' => AdminRole::CUSTOMER_SUPPORT,
                'language' => Language::VIETNAMESE->value,
                'is_active' => true,
            ]);
    }
}
