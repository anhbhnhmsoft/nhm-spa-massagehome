<?php

namespace Database\Seeders;

use App\Core\Helper;
use App\Enums\ConfigName;
use App\Enums\ConfigType;
use App\Enums\Gender;
use App\Enums\Language;
use App\Enums\ReviewApplicationStatus;
use App\Enums\ServiceDuration;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Config;
use App\Models\Province;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
//        $this->seedProvince();
//        $this->seedCategory();
//        $this->seedAdmin();
//        $this->seedKTV();

//        $this->seedConfig();
    }

    protected function seedAdmin(): void
    {
        DB::beginTransaction();
        try {
            // Seeding admin trước để có thể login vào hệ thống
            $admin = User::query()->create([
                'phone' => '012345678910',
                'phone_verified_at' => now(),
                'password' => bcrypt('Test1234568@'),
                'name' => 'Admin System',
                'role' => UserRole::ADMIN->value,
                'language' => Language::VIETNAMESE->value,
                'is_active' => true,
                'referral_code' => 'ADMIN001',
            ]);
        } catch (\Exception $exception) {
            dump($exception);
            DB::rollBack();
        }
        DB::commit();
    }

    protected function seedKTV(): void
    {
        $provinces = Province::query()->pluck('code')->toArray();
        $categories = Category::query()->pluck('id')->toArray();
        $fake = fake('vi');
        DB::beginTransaction();
        try {
            foreach (range(1, 10) as $index) {
                $gender = $fake->randomElement(Gender::cases());
                $provinceCode = $fake->randomElement($provinces);
                $address = $fake->address();
                $latitude = $fake->latitude();
                $longitude = $fake->longitude();
                $bio = $fake->paragraph();

                $user = User::query()->create([
                    'phone' => $fake->phoneNumber(),
                    'phone_verified_at' => now(),
                    'password' => bcrypt('Test1234568@'),
                    'name' => $fake->name(),
                    'role' => UserRole::KTV->value,
                    'language' => Language::VIETNAMESE->value,
                    'is_active' => true,
                    'referral_code' => Helper::generateReferCodeUser(UserRole::KTV),
                ]);

                $wallet = $user->wallet()->create([
                    'balance' => 0,
                ]);

                $profile = $user->profile()->create([
                    'user_id' => $user->id,
                    'avatar_url' => $fake->imageUrl(),
                    'date_of_birth' => $fake->date(),
                    'gender' => $gender->value,
                    'bio' => $bio,
                ]);

                $reviewApplication = $user->reviewApplication()->create([
                    'user_id' => $user->id,
                    'status' => ReviewApplicationStatus::APPROVED->value,
                    'province_code' => $provinceCode,
                    'address' => $address,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'bio' => $bio,
                    'experience' => fake()->numberBetween(0, 10),
                    'skills' => [
                        $fake->randomElement(['Massage toàn thân', 'Massage tay', 'Massage Thụy Điển', 'Massage đầu']),
                        $fake->randomElement(['Massage toàn thân', 'Massage tay', 'Massage Thụy Điển', 'Massage đầu']),
                        $fake->randomElement(['Massage toàn thân', 'Massage tay', 'Massage Thụy Điển', 'Massage đầu']),
                        $fake->randomElement(['Massage toàn thân', 'Massage tay', 'Massage Thụy Điển', 'Massage đầu']),
                    ],
                ]);
                foreach (range(1, 5) as $index) {
                    $service = $user->services()->create([
                        'name' => $fake->paragraph(1),
                        'category_id' => $fake->randomElement($categories),
                        'description' => $fake->paragraph(),
                        'is_active' => true,
                        'image_url' => $fake->imageUrl(),
                    ]);
                    foreach ([
                                 ServiceDuration::FIFTEEN_MINUTE->value,
                                 ServiceDuration::HALF_HOUR->value,
                                 ServiceDuration::ONE_HOUR->value,
                                 ServiceDuration::ONE_AND_HALF_HOUR->value,
                                 ServiceDuration::TWO_HOUR->value,
                             ] as $duration) {
                        $service->options()->create([
                            'duration' => $duration,
                            'price' => $fake->numberBetween(100000, 500000),
                        ]);
                    }
                }

            }
        } catch (\Exception $exception) {
            dump($exception);
            DB::rollBack();
        }
        DB::commit();
    }

    /**
     *
     * @return bool
     */
    protected function seedCategory(): bool
    {
        DB::beginTransaction();
        try {
            Category::query()->create([
                'name' => 'Massage toàn thân',
                'description' => fake('vi')->paragraph(),
                'image_url' => fake()->imageUrl(),
                'position' => 1,
                'is_featured' => true,
                'usage_count' => fake()->numberBetween(0, 100),
                'is_active' => true,
            ]);
            Category::query()->create([
                'name' => 'Massage tay',
                'description' => fake('vi')->paragraph(),
                'image_url' => fake()->imageUrl(),
                'position' => 2,
                'is_featured' => true,
                'usage_count' => fake()->numberBetween(0, 100),
                'is_active' => true,
            ]);
            Category::query()->create([
                'name' => 'Massage Thụy Điển',
                'description' => fake('vi')->paragraph(),
                'image_url' => fake()->imageUrl(),
                'position' => 3,
                'is_featured' => true,
                'usage_count' => fake()->numberBetween(0, 100),
                'is_active' => true,
            ]);
            Category::query()->create([
                'name' => 'Massage đầu',
                'description' => fake('vi')->paragraph(),
                'image_url' => fake()->imageUrl(),
                'position' => 4,
                'is_featured' => false,
                'usage_count' => fake()->numberBetween(0, 100),
                'is_active' => true,
            ]);
            Category::query()->create([
                'name' => 'Massage chân',
                'description' => fake('vi')->paragraph(),
                'image_url' => fake()->imageUrl(),
                'position' => 5,
                'is_featured' => false,
                'usage_count' => fake()->numberBetween(0, 100),
                'is_active' => true,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            dump($e);
            return false;
        }
        DB::commit();
        return true;
    }

    /**
     * Seed province from open-api.vn
     * @return bool
     */
    protected function seedProvince(): bool
    {
        DB::beginTransaction();
        try {
            $responseProvince = Http::get('https://provinces.open-api.vn/api/v1/p/');
            if ($responseProvince->successful()) {
                $data = $responseProvince->json();  // Lấy dữ liệu dưới dạng mảng
                // Lưu dữ liệu vào bảng provinces
                foreach ($data as $provinceData) {
                    Province::query()->updateOrCreate(
                        ['code' => $provinceData['code']],
                        [
                            'name' => $provinceData['name'],
                            'division_type' => $provinceData["division_type"],
                        ]
                    );
                }
            } else {
                DB::rollBack();
                return false;
            }
            DB::commit();
            return true;
        } catch (\Exception $exception) {
            DB::rollBack();
            dump($exception);
            return false;
        }
    }

    protected function seedConfig(): bool
    {
        DB::beginTransaction();
        try {
            Config::query()->updateOrCreate(
                ['config_key' => ConfigName::PAYOS_CLIENT_ID->value],
                [
                    'config_value' => '3199a47f-9162-4d98-9908-9732432cf365',
                    'config_type' => ConfigType::STRING->value,
                    'description' => 'Mã client ID PayOS dùng để tích hợp thanh toán PayOS.',
                ]
            );
            Config::query()->updateOrCreate(
                ['config_key' => ConfigName::PAYOS_API_KEY->value],
                [
                    'config_value' => '18e78b87-1300-4e2d-adef-0de1423d89a8',
                    'config_type' => ConfigType::STRING->value,
                    'description' => 'Mã secret key PayOS dùng để tích hợp thanh toán PayOS.',
                ]
            );
            Config::query()->updateOrCreate(
                ['config_key' => ConfigName::PAYOS_CHECKSUM_KEY->value],
                [
                    'config_value' => 'bcdaecc9ad73edff55c076519e5ab5e127fd9681c13cfd19de1dfa29d9d8fce9',
                    'config_type' => ConfigType::STRING->value,
                    'description' => 'Mã checksum key PayOS dùng để tích hợp thanh toán PayOS.',
                ]
            );

        } catch (\Exception $e) {
            DB::rollBack();
            dump($e);
            return false;
        }
        DB::commit();
        return true;
    }
}
