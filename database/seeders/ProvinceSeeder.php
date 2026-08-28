<?php

namespace Database\Seeders;

use App\Models\Province;
use Illuminate\Database\Seeder;

class ProvinceSeeder extends Seeder
{
    public function run(): void
    {
        $provinces = [
            ['code' => '79', 'name' => 'TP. Hồ Chí Minh', 'division_type' => 'Thành phố Trung ương'],
            ['code' => '01', 'name' => 'TP. Hà Nội', 'division_type' => 'Thành phố Trung ương'],
            ['code' => '48', 'name' => 'TP. Đà Nẵng', 'division_type' => 'Thành phố Trung ương'],
            ['code' => '92', 'name' => 'TP. Cần Thơ', 'division_type' => 'Thành phố Trung ương'],
            ['code' => '31', 'name' => 'TP. Hải Phòng', 'division_type' => 'Thành phố Trung ương'],
            ['code' => '77', 'name' => 'Bà Rịa - Vũng Tàu', 'division_type' => 'Tỉnh'],
            ['code' => '24', 'name' => 'Bắc Giang', 'division_type' => 'Tỉnh'],
            ['code' => '06', 'name' => 'Bắc Kạn', 'division_type' => 'Tỉnh'],
            ['code' => '95', 'name' => 'Bạc Liêu', 'division_type' => 'Tỉnh'],
            ['code' => '27', 'name' => 'Bắc Ninh', 'division_type' => 'Tỉnh'],
            ['code' => '83', 'name' => 'Bến Tre', 'division_type' => 'Tỉnh'],
            ['code' => '52', 'name' => 'Bình Định', 'division_type' => 'Tỉnh'],
            ['code' => '74', 'name' => 'Bình Dương', 'division_type' => 'Tỉnh'],
            ['code' => '70', 'name' => 'Bình Phước', 'division_type' => 'Tỉnh'],
            ['code' => '60', 'name' => 'Bình Thuận', 'division_type' => 'Tỉnh'],
            ['code' => '96', 'name' => 'Cà Mau', 'division_type' => 'Tỉnh'],
            ['code' => '04', 'name' => 'Cao Bằng', 'division_type' => 'Tỉnh'],
            ['code' => '66', 'name' => 'Đắk Lắk', 'division_type' => 'Tỉnh'],
            ['code' => '67', 'name' => 'Đắk Nông', 'division_type' => 'Tỉnh'],
            ['code' => '11', 'name' => 'Điện Biên', 'division_type' => 'Tỉnh'],
            ['code' => '75', 'name' => 'Đồng Nai', 'division_type' => 'Tỉnh'],
            ['code' => '87', 'name' => 'Đồng Tháp', 'division_type' => 'Tỉnh'],
            ['code' => '64', 'name' => 'Gia Lai', 'division_type' => 'Tỉnh'],
            ['code' => '02', 'name' => 'Hà Giang', 'division_type' => 'Tỉnh'],
            ['code' => '35', 'name' => 'Hà Nam', 'division_type' => 'Tỉnh'],
            ['code' => '42', 'name' => 'Hà Tĩnh', 'division_type' => 'Tỉnh'],
            ['code' => '30', 'name' => 'Hải Dương', 'division_type' => 'Tỉnh'],
            ['code' => '93', 'name' => 'Hậu Giang', 'division_type' => 'Tỉnh'],
            ['code' => '17', 'name' => 'Hòa Bình', 'division_type' => 'Tỉnh'],
            ['code' => '33', 'name' => 'Hưng Yên', 'division_type' => 'Tỉnh'],
            ['code' => '56', 'name' => 'Khánh Hòa', 'division_type' => 'Tỉnh'],
            ['code' => '91', 'name' => 'Kiên Giang', 'division_type' => 'Tỉnh'],
            ['code' => '62', 'name' => 'Kon Tum', 'division_type' => 'Tỉnh'],
            ['code' => '12', 'name' => 'Lai Châu', 'division_type' => 'Tỉnh'],
            ['code' => '68', 'name' => 'Lâm Đồng', 'division_type' => 'Tỉnh'],
            ['code' => '20', 'name' => 'Lạng Sơn', 'division_type' => 'Tỉnh'],
            ['code' => '10', 'name' => 'Lào Cai', 'division_type' => 'Tỉnh'],
            ['code' => '80', 'name' => 'Long An', 'division_type' => 'Tỉnh'],
            ['code' => '36', 'name' => 'Nam Định', 'division_type' => 'Tỉnh'],
            ['code' => '40', 'name' => 'Nghệ An', 'division_type' => 'Tỉnh'],
            ['code' => '37', 'name' => 'Ninh Bình', 'division_type' => 'Tỉnh'],
            ['code' => '58', 'name' => 'Ninh Thuận', 'division_type' => 'Tỉnh'],
            ['code' => '25', 'name' => 'Phú Thọ', 'division_type' => 'Tỉnh'],
            ['code' => '54', 'name' => 'Phú Yên', 'division_type' => 'Tỉnh'],
            ['code' => '44', 'name' => 'Quảng Bình', 'division_type' => 'Tỉnh'],
            ['code' => '49', 'name' => 'Quảng Nam', 'division_type' => 'Tỉnh'],
            ['code' => '51', 'name' => 'Quảng Ngãi', 'division_type' => 'Tỉnh'],
            ['code' => '22', 'name' => 'Quảng Ninh', 'division_type' => 'Tỉnh'],
            ['code' => '45', 'name' => 'Quảng Trị', 'division_type' => 'Tỉnh'],
            ['code' => '94', 'name' => 'Sóc Trăng', 'division_type' => 'Tỉnh'],
            ['code' => '14', 'name' => 'Sơn La', 'division_type' => 'Tỉnh'],
            ['code' => '72', 'name' => 'Tây Ninh', 'division_type' => 'Tỉnh'],
            ['code' => '34', 'name' => 'Thái Bình', 'division_type' => 'Tỉnh'],
            ['code' => '19', 'name' => 'Thái Nguyên', 'division_type' => 'Tỉnh'],
            ['code' => '38', 'name' => 'Thanh Hóa', 'division_type' => 'Tỉnh'],
            ['code' => '46', 'name' => 'Thừa Thiên Huế', 'division_type' => 'Tỉnh'],
            ['code' => '82', 'name' => 'Tiền Giang', 'division_type' => 'Tỉnh'],
            ['code' => '84', 'name' => 'Trà Vinh', 'division_type' => 'Tỉnh'],
            ['code' => '08', 'name' => 'Tuyên Quang', 'division_type' => 'Tỉnh'],
            ['code' => '86', 'name' => 'Vĩnh Long', 'division_type' => 'Tỉnh'],
            ['code' => '26', 'name' => 'Vĩnh Phúc', 'division_type' => 'Tỉnh'],
            ['code' => '15', 'name' => 'Yên Bái', 'division_type' => 'Tỉnh'],
            ['code' => '89', 'name' => 'An Giang', 'division_type' => 'Tỉnh'],
        ];

        foreach ($provinces as $p) {
            Province::query()->updateOrCreate(['code' => $p['code']], $p);
        }
    }
}
