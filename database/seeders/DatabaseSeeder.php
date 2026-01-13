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
use App\Models\AffiliateConfig;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Config;
use App\Models\Coupon;
use App\Models\Province;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->seedProvince();
        $this->seedCategory();
        $this->seedAdmin();
//        $this->seedKTV();
        $this->seedCoupon();
        $this->seedConfig();
        $this->seedConfigAffiliate();
    }

    protected function seedAdmin(): void
    {
        DB::beginTransaction();
        try {
            // Seeding admin trước để có thể login vào hệ thống
            $admin = User::query()->create([
                'phone' => '012345678910',
                'phone_verified_at' => now(),
                'password' => Hash::make('Test12345678@'),
                'name' => 'Admin System',
                'role' => UserRole::ADMIN->value,
                'language' => Language::VIETNAMESE->value,
                'is_active' => true,
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
                    'bio' => [
                        Language::VIETNAMESE->value => fake('vi_VN')->paragraph(),
                        Language::ENGLISH->value => fake('en_US')->paragraph(),
                        Language::CHINESE->value => fake('zh_CN')->paragraph(),
                    ],
                    'experience' => fake()->numberBetween(0, 10),

                ]);
                foreach (range(1, 5) as $index) {
                    $service = $user->services()->create([
                        'name' => [
                            Language::VIETNAMESE->value => fake('vi_VN')->paragraph(1),
                            Language::ENGLISH->value => fake('en_US')->paragraph(1),
                            Language::CHINESE->value => fake('zh_CN')->paragraph(1),
                        ],
                        'category_id' => $fake->randomElement($categories),
                        'description' => [
                            Language::VIETNAMESE->value => fake('vi_VN')->paragraph(),
                            Language::ENGLISH->value => fake('en_US')->paragraph(),
                            Language::CHINESE->value => fake('zh_CN')->paragraph(),
                        ],
                        'is_active' => true,
                        'image_url' => $fake->imageUrl(),
                    ]);
                    foreach (
                        [
                            ServiceDuration::FIFTEEN_MINUTE->value,
                            ServiceDuration::HALF_HOUR->value,
                            ServiceDuration::ONE_HOUR->value,
                            ServiceDuration::ONE_AND_HALF_HOUR->value,
                            ServiceDuration::TWO_HOUR->value,
                        ] as $duration
                    ) {
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
                'name' => [
                    Language::VIETNAMESE->value => 'Massage toàn thân',
                    Language::ENGLISH->value => 'Full Body Massage',
                    Language::CHINESE->value => '全身按摩',
                ],
                'description' => [
                    Language::VIETNAMESE->value => 'Liệu trình massage toàn thân giúp thư giãn cơ bắp, cải thiện tuần hoàn máu và giảm căng thẳng sau ngày dài mệt mỏi.',
                    Language::ENGLISH->value => 'A full body massage therapy that helps relax muscles, improve blood circulation, and relieve stress after a long day.',
                    Language::CHINESE->value => '全身按摩疗程，有助于放松肌肉、促进血液循环，并缓解一天后的疲劳与压力。',
                ],
                'image_url' => null,
                'position' => 1,
                'is_featured' => true,
                'usage_count' => fake()->numberBetween(0, 100),
                'is_active' => true,
            ]);
            Category::query()->create([
                'name' => [
                    Language::VIETNAMESE->value => 'Massage tay',
                    Language::ENGLISH->value => 'Hand Massage',
                    Language::CHINESE->value => '手 massage',
                ],
                'description' => [
                    Language::VIETNAMESE->value => 'Liệu trình massage toàn thân giúp thư giãn cơ bắp, cải thiện tuần hoàn máu và giảm căng thẳng sau ngày dài mệt mỏi.',
                    Language::ENGLISH->value => 'A full body massage therapy that helps relax muscles, improve blood circulation, and relieve stress after a long day.',
                    Language::CHINESE->value => '全身按摩疗程，有助于放松肌肉、促进血液循环，并缓解一天后的疲劳与压力。',
                ],
                'image_url' => null,
                'position' => 2,
                'is_featured' => true,
                'usage_count' => fake()->numberBetween(0, 100),
                'is_active' => true,
            ]);
            Category::query()->create([
                'name' => [
                    Language::VIETNAMESE->value => 'Massage toàn thân',
                    Language::ENGLISH->value => 'Full Body Massage',
                    Language::CHINESE->value => '全身按摩',
                ],
                'description' => [
                    Language::VIETNAMESE->value => 'Liệu trình chuyên sâu cho vùng cổ, vai và gáy, giúp giảm đau nhức do ngồi lâu hoặc làm việc căng thẳng.',
                    Language::ENGLISH->value => 'A targeted therapy for neck and shoulders to relieve pain caused by long sitting or work stress.',
                    Language::CHINESE->value => '针对颈部和肩部的深层按摩，有效缓解久坐或工作压力引起的酸痛。',
                ],
                'image_url' => null,
                'position' => 3,
                'is_featured' => true,
                'usage_count' => fake()->numberBetween(0, 100),
                'is_active' => true,
            ]);
            Category::query()->create([
                'name' => [
                    Language::VIETNAMESE->value => 'Massage đầu',
                    Language::ENGLISH->value => 'Head Massage',
                    Language::CHINESE->value => '头 massage',
                ],
                'description' => [
                    Language::VIETNAMESE->value => 'Massage da đầu giúp thư giãn thần kinh, cải thiện giấc ngủ và giảm căng thẳng tinh thần.',
                    Language::ENGLISH->value => 'A head massage that relaxes the nervous system, improves sleep quality, and reduces mental stress.',
                    Language::CHINESE->value => '头部按摩有助于放松神经系统，改善睡眠质量并缓解精神压力。',
                ],
                'image_url' => null,
                'position' => 4,
                'is_featured' => false,
                'usage_count' => fake()->numberBetween(0, 100),
                'is_active' => true,
            ]);
            Category::query()->create([
                'name' => [
                    Language::VIETNAMESE->value => 'Massage chân',
                    Language::ENGLISH->value => 'Leg Massage',
                    Language::CHINESE->value => '脚 massage',
                ],
                'description' => [
                    Language::VIETNAMESE->value => 'Massage chân giúp giảm sưng mỏi, kích thích huyệt đạo và cải thiện lưu thông máu.',
                    Language::ENGLISH->value => 'A leg and foot massage that reduces swelling, stimulates acupressure points, and improves blood circulation.',
                    Language::CHINESE->value => '足部按摩有助于缓解肿胀与疲劳，刺激穴位并促进血液循环。',
                ],
                'image_url' => null,
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
            Config::query()->updateOrCreate(
                ['config_key' => ConfigName::CURRENCY_EXCHANGE_RATE->value],
                [
                    'config_value' => '1000',
                    'config_type' => ConfigType::NUMBER->value,
                    'description' => 'Tỷ giá đổi tiền VNĐ -> Point VD: 1000 VNĐ = 1 Point',
                ]
            );

            Config::query()->updateOrCreate(
                ['config_key' => ConfigName::GOONG_API_KEY->value],
                [
                    'config_value' => 'dpxUCPncHcWczUMJY5EfatOFCpxGI2tB9ADsR4sb',
                    'config_type' => ConfigType::STRING->value,
                    'description' => 'Mã API key Goong dùng để tích hợp tìm kiếm địa chỉ.',
                ]
            );
            Config::query()->updateOrCreate(
                ['config_key' => ConfigName::BREAK_TIME_GAP->value],
                [
                    'config_value' => '60',
                    'config_type' => ConfigType::NUMBER->value,
                    'description' => 'Khoảng cách giữa 2 lần phục vụ của kỹ thuật viên tính bằng phút',
                ]
            );
            Config::query()->updateOrCreate(
                ['config_key' => ConfigName::DISCOUNT_RATE->value],
                [
                    'config_value' => '20',
                    'config_type' => ConfigType::NUMBER->value,
                    'description' => 'Tỷ lệ chiết khấu ứng dụng nhận được',
                ]
            );
            Config::query()->updateOrCreate(
                ['config_key' => ConfigName::DISCOUNT_RATE_REFERRER_AGENCY->value],
                [
                    'config_value' => '15',
                    'config_type' => ConfigType::NUMBER->value,
                    'description' => 'Tỷ lệ chiết khấu dành cho đại lý đối với 1 đơn hoàn thành của 1 KTV mà mình giới thiệu %',
                ]
            );
            Config::query()->updateOrCreate(
                ['config_key' => ConfigName::DISCOUNT_RATE_REFERRER_KTV->value],
                [
                    'config_value' => '5',
                    'config_type' => ConfigType::NUMBER->value,
                    'description' => 'Tỷ lệ chiết khấu dành cho kỹ thuật viên đối với 1 đơn hoàn thành của 1 KTV mà mình giới thiệu %',
                ]
            );
            Config::query()->updateOrCreate(
                ['config_key' => ConfigName::DISCOUNT_RATE_REFERRER_KTV_LEADER->value],
                [
                    'config_value' => '10',
                    'config_type' => ConfigType::NUMBER->value,
                    'description' => 'Tỷ lệ chiết khấu dành cho kỹ thuật viên trưởng đối với 1 đơn hoàn thành của 1 KTV mà mình giới thiệu %',
                ]
            );
            Config::query()->updateOrCreate(
                ['config_key' => ConfigName::SP_PHONE->value],
                [
                    'config_value' => '0865643858',
                    'config_type' => ConfigType::STRING->value,
                    'description' => 'Số điện thoại hỗ trợ',
                ]
            );
            Config::query()->updateOrCreate(
                ['config_key' => ConfigName::SP_ZALO->value],
                [
                    'config_value' => 'https://zalo.me/0865643858',
                    'config_type' => ConfigType::STRING->value,
                    'description' => 'Trang Zalo hỗ trợ của admin',
                ]
            );

            Config::query()->updateOrCreate(
                ['config_key' => ConfigName::SP_FACEBOOK->value],
                [
                    'config_value' => 'https://facebook.com/admin.support',
                    'config_type' => ConfigType::STRING->value,
                    'description' => 'Trang Facebook hỗ trợ của admin',
                ]
            );

            Config::query()->updateOrCreate(
                ['config_key' => ConfigName::SP_WECHAT->value],
                [
                    'config_value' => 'wechat_admin_support',
                    'config_type' => ConfigType::STRING->value,
                    'description' => 'Link WeChat hỗ trợ của admin',
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

    public function seedConfigAffiliate(): bool
    {
        DB::beginTransaction();
        try {
            AffiliateConfig::query()->updateOrCreate(
                ['target_role' => UserRole::AGENCY->value],
                [
                    'name' => 'Cấu hình dành cho đại lý',
                    'commission_rate' => '10',
                    'min_commission' => '10000',
                    'max_commission' => '100000',
                    'description' => 'Tỷ lệ hoa hồng đại lý',
                ]
            );
            AffiliateConfig::query()->updateOrCreate(
                ['target_role' => UserRole::KTV->value],
                [
                    'name' => 'Cấu hình dành cho kỹ thuật viên',
                    'commission_rate' => '10',
                    'min_commission' => '10000',
                    'max_commission' => '100000',
                    'description' => 'Tỷ lệ hoa hồng kỹ thuật viên',
                ]
            );
            AffiliateConfig::query()->updateOrCreate(
                ['target_role' => UserRole::CUSTOMER->value],
                [
                    'name' => 'Cấu hình dành cho khách hàng',
                    'commission_rate' => '10',
                    'min_commission' => '10000',
                    'max_commission' => '100000',
                    'description' => 'Tỷ lệ hoa hồng khách hàng',
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

    protected function seedCoupon()
    {
        DB::beginTransaction();
        try {
            Coupon::query()->updateOrCreate(
                [
                    'code' => 'NEW20',
                    'created_by' => User::query()->where('phone', '012345678910')->first()->id,
                ],
                [
                    'label' => [
                        Language::VIETNAMESE->value => 'Ưu đãi đặc biệt 20%',
                        Language::ENGLISH->value => 'Special discount 20%',
                        Language::CHINESE->value => '特殊折扣 20%',
                    ],
                    'description' => [
                        Language::VIETNAMESE->value => 'Ưu đãi đặc biệt 20% cho khách hàng',
                        Language::ENGLISH->value => 'Special discount 20% for customers',
                        Language::CHINESE->value => '特殊折扣 20% 对客户',
                    ],
                    'created_by' => User::query()->where('phone', '012345678910')->first()->id,
                    'for_service_id' => null,
                    'is_percentage' => true,
                    'discount_value' => 20,
                    'max_discount' => 100000,
                    'start_at' => now(),
                    'end_at' => now()->addMonth(),
                    'usage_limit' => 100,
                    'used_count' => 0,
                    'is_active' => true,
                ]
            );
            Coupon::query()->updateOrCreate(
                [
                    'code' => 'NEW40',
                    'created_by' => User::query()->where('phone', '012345678910')->first()->id,
                ],
                [
                    'label' => [
                        Language::VIETNAMESE->value => 'Ưu đãi đặc biệt 40%',
                        Language::ENGLISH->value => 'Special discount 40%',
                        Language::CHINESE->value => '特殊折扣 40%',
                    ],
                    'description' => [
                        Language::VIETNAMESE->value => 'Ưu đãi đặc biệt 40% cho khách hàng',
                        Language::ENGLISH->value => 'Special discount 40% for customers',
                        Language::CHINESE->value => '特殊折扣 40% 对客户',
                    ],
                    'created_by' => User::query()->where('phone', '012345678910')->first()->id,
                    'for_service_id' => null,
                    'is_percentage' => true,
                    'discount_value' => 40,
                    'max_discount' => 100000,
                    'start_at' => now(),
                    'end_at' => now()->addMonth(),
                    'usage_limit' => 100,
                    'used_count' => 0,
                    'is_active' => true,
                ]
            );
            Coupon::query()->updateOrCreate(
                [
                    'code' => 'NEW50',
                    'created_by' => User::query()->where('phone', '012345678910')->first()->id,
                ],
                [
                    'label' => [
                        Language::VIETNAMESE->value => 'Ưu đãi đặc biệt 50%',
                        Language::ENGLISH->value => 'Special discount 50%',
                        Language::CHINESE->value => '特殊折扣 50%',
                    ],
                    'description' => [
                        Language::VIETNAMESE->value => 'Ưu đãi đặc biệt 50% cho khách hàng',
                        Language::ENGLISH->value => 'Special discount 50% for customers',
                        Language::CHINESE->value => '特殊折扣 50% 对客户',
                    ],
                    'created_by' => User::query()->where('phone', '012345678910')->first()->id,
                    'for_service_id' => null,
                    'is_percentage' => true,
                    'discount_value' => 40,
                    'max_discount' => 100000,
                    'start_at' => now(),
                    'end_at' => now()->addMonth(),
                    'usage_limit' => 100,
                    'used_count' => 0,
                    'is_active' => true,
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
