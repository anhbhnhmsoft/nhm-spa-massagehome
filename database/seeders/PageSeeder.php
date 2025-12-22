<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Page::updateOrCreate(
            ['slug' => 'about-us'],
            [
                'title' => [
                    'en' => 'About Us',
                    'vi' => 'Về chúng tôi',
                    'cn' => '关于我们',
                ],
                'content' => [
                    'en' => '<h2>Massage Home - Wellness at Your Doorstep</h2><p>Founded in 2024, Massage Home has quickly become the leading provider of premium on-demand spa services.</p>',
                    'vi' => '<h2>Massage Home - Sức khỏe tại gia</h2><p>Được thành lập vào năm 2024, Massage Home đã nhanh chóng trở thành đơn vị cung cấp dịch vụ spa tại nhà cao cấp hàng đầu.</p>',
                    'cn' => '<h2>按摩之家 - 居家健康</h2><p>按摩之家成立于2024年，迅速发展成为领先的高端上门水疗服务提供商。</p>'
                ],
                'meta_title' => [
                    'en' => 'About Us | Massage Home',
                    'vi' => 'Về chúng tôi | Massage Home',
                    'cn' => '关于我们 | Massage Home'
                ],
                'meta_description' => [
                    'en' => 'Learn more about Massage Home and our mission.',
                    'vi' => 'Tìm hiểu thêm về Massage Home và sứ mệnh của chúng tôi.',
                    'cn' => '了解更多关于Massage Home和我们的使命。'
                ],
                'meta_keywords' => [
                    'en' => 'about, massage, wellness',
                    'vi' => 'giới thiệu, massage, sức khỏe',
                    'cn' => '关于, 按摩, 健康'
                ],
                'og_image' => 'https://images.unsplash.com/photo-1544161515-4ab6ce6db874?auto=format&fit=crop&q=80&w=800',
                'is_active' => true,
            ]
        );
    }
}
