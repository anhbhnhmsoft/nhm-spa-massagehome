<?php

namespace Database\Seeders;

use App\Enums\CustomerRank;
use App\Enums\DemandStatus;
use App\Enums\Gender;
use App\Enums\UserRole;
use App\Models\AdminUser;
use App\Models\CustomerCrmData;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cskhStaff = AdminUser::query()->pluck('id')->toArray();
        $cskhNgoc = $cskhStaff[0] ?? null;
        $cskhHoang = $cskhStaff[1] ?? null;
        $cskhMai = $cskhStaff[2] ?? null;
        $cskhBao = $cskhStaff[3] ?? null;

        $customers = [
            [
                'phone' => '0903112233',
                'name' => 'Lê Hoàng Long',
                'email' => 'long.le@example.com',
                'province' => 'TP. Hồ Chí Minh',
                'ward' => 'Phường Tân Định',
                'gender' => Gender::MALE->value,
                'date_of_birth' => '1988-06-15',
                'temp_address' => '78 Hai Bà Trưng, Phường Tân Định, Quận 1, TP.HCM',
                'bio' => 'Khách hàng VIP, thích massage thư giãn',
                'demand_status' => DemandStatus::NEED_NOW,
                'customer_rank' => CustomerRank::VIP,
                'assigned_cskh_id' => $cskhNgoc,
                'total_spent' => 5400000,
                'booking_count' => 8,
                'aov' => 675000,
                'cskh_notes' => 'Khách VIP, cần kỹ thuật viên massage bấm huyệt trị liệu tại nhà ngay tối nay.',
            ],
            [
                'phone' => '0934556677',
                'name' => 'Phạm Minh Tuấn',
                'email' => 'tuan.pham@example.com',
                'province' => 'TP. Hồ Chí Minh',
                'ward' => 'Phường Đa Kao',
                'gender' => Gender::MALE->value,
                'date_of_birth' => '1990-11-20',
                'temp_address' => '15 Đinh Tiên Hoàng, Phường Đa Kao, Quận 1, TP.HCM',
                'bio' => 'Khách hàng Gold, chuộng massage đá nóng',
                'demand_status' => DemandStatus::BOOKED,
                'customer_rank' => CustomerRank::GOLD,
                'assigned_cskh_id' => $cskhHoang,
                'total_spent' => 2800000,
                'booking_count' => 4,
                'aov' => 700000,
                'cskh_notes' => 'Đã chốt lịch massage tinh dầu thảo dược 90 phút vào thứ Bảy lúc 18:30.',
            ],
            [
                'phone' => '0977889900',
                'name' => 'Hoàng Thùy Linh',
                'email' => 'linh.hoang@example.com',
                'province' => 'TP. Hồ Chí Minh',
                'ward' => 'Phường Cầu Kho',
                'gender' => Gender::FEMALE->value,
                'date_of_birth' => '1996-03-12',
                'temp_address' => '234 Trần Hưng Đạo, Phường Cầu Kho, Quận 1, TP.HCM',
                'bio' => 'Khách mới đăng ký app',
                'demand_status' => DemandStatus::NO_NEED,
                'customer_rank' => CustomerRank::STANDARD,
                'assigned_cskh_id' => $cskhMai,
                'total_spent' => 0,
                'booking_count' => 0,
                'aov' => 0,
                'cskh_notes' => 'Khách vừa hỏi thông tin bảng giá combo nhưng hiện tại chưa có nhu cầu đặt lịch.',
            ],
            [
                'phone' => '0945667788',
                'name' => 'Đỗ Phương Thảo',
                'email' => 'thao.do@example.com',
                'province' => 'TP. Hồ Chí Minh',
                'ward' => 'Phường Thảo Điền',
                'gender' => Gender::FEMALE->value,
                'date_of_birth' => '1994-08-25',
                'temp_address' => '56 Quốc Hương, Phường Thảo Điền, TP. Thủ Đức, TP.HCM',
                'bio' => 'Khách tìm hiểu dịch vụ mẹ bầu',
                'demand_status' => DemandStatus::EXPLORING,
                'customer_rank' => CustomerRank::STANDARD,
                'assigned_cskh_id' => $cskhBao,
                'total_spent' => 650000,
                'booking_count' => 1,
                'aov' => 650000,
                'cskh_notes' => 'Khách đang tìm hiểu gói massage cổ vai gáy cho dân văn phòng và liệu trình bà bầu.',
            ],
            [
                'phone' => '0918223344',
                'name' => 'Vũ Đình Trọng',
                'email' => 'trong.vu@example.com',
                'province' => 'TP. Hồ Chí Minh',
                'ward' => 'Phường Bến Thành',
                'gender' => Gender::MALE->value,
                'date_of_birth' => '1985-02-18',
                'temp_address' => '88 Lê Thánh Tôn, Phường Bến Thành, Quận 1, TP.HCM',
                'bio' => 'Khách doanh nhân bận rộn',
                'demand_status' => DemandStatus::NEED_NOW,
                'customer_rank' => CustomerRank::GOLD,
                'assigned_cskh_id' => null,
                'total_spent' => 1950000,
                'booking_count' => 3,
                'aov' => 650000,
                'cskh_notes' => 'Khách mới gửi yêu cầu massage tại nhà qua app, cần phục vụ gấp trong 1 giờ tới.',
            ],
            [
                'phone' => '0966334455',
                'name' => 'Bùi Mai Lan',
                'email' => 'lan.bui@example.com',
                'province' => 'TP. Hồ Chí Minh',
                'ward' => 'Phường Nguyễn Thái Bình',
                'gender' => Gender::FEMALE->value,
                'date_of_birth' => '1998-12-05',
                'temp_address' => '102 Calmette, Phường Nguyễn Thái Bình, Quận 1, TP.HCM',
                'bio' => 'Khách VIP trung thành',
                'demand_status' => DemandStatus::BOOKED,
                'customer_rank' => CustomerRank::VIP,
                'assigned_cskh_id' => $cskhNgoc,
                'total_spent' => 6200000,
                'booking_count' => 9,
                'aov' => 688888.89,
                'cskh_notes' => 'Khách VIP quen thuộc của spa, đã đặt trước combo chăm sóc body toàn diện cuối tuần.',
            ],
        ];

        DB::transaction(function () use ($customers) {
            foreach ($customers as $item) {
                $user = User::updateOrCreate(
                    ['phone' => $item['phone']],
                    [
                        'name' => $item['name'],
                        'email' => $item['email'],
                        'password' => Hash::make('123456@'),
                        'role' => UserRole::CUSTOMER->value,
                        'language' => 'vn',
                        'is_active' => true,
                        'is_online' => true,
                        'ward' => $item['ward'],
                        'province' => $item['province'],
                        'phone_verified_at' => now(),
                    ]
                );

                UserProfile::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'gender' => $item['gender'],
                        'date_of_birth' => $item['date_of_birth'],
                        'bio' => $item['bio'],
                        'temp_address' => $item['temp_address'],
                    ]
                );

                CustomerCrmData::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'languages' => ['vi'],
                        'demand_status' => $item['demand_status'],
                        'customer_rank' => $item['customer_rank'],
                        'assigned_cskh_id' => $item['assigned_cskh_id'],
                        'total_spent' => $item['total_spent'],
                        'booking_count' => $item['booking_count'],
                        'aov' => $item['aov'],
                        'cskh_notes' => $item['cskh_notes'],
                    ]
                );
            }
        });
    }
}
