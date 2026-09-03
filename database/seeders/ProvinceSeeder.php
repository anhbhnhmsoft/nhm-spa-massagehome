<?php

namespace Database\Seeders;

use App\Models\Province;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class ProvinceSeeder extends Seeder
{
    public function run(): void
    {
        try {
            $res = Http::timeout(10)->get('https://provinces.open-api.vn/api/?depth=1');
            if ($res->successful()) {
                $list = $res->json();
                foreach ($list as $item) {
                    Province::updateOrCreate(
                        ['code' => sprintf('%02d', (int) $item['code'])],
                        [
                            'name' => $item['name'],
                            'division_type' => $item['division_type'],
                        ]
                    );
                }
            }
        } catch (\Throwable $e) {
            // Fallback nếu không có kết nối mạng
        }
    }
}
