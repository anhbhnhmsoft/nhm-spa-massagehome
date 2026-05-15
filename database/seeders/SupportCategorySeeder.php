<?php

namespace Database\Seeders;

use App\Enums\Language;
use App\Models\SupportCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupportCategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::beginTransaction();
        try {
            $items = [
                [
                    'position' => 1,
                    'name' => [
                        Language::VIETNAMESE->value => 'Hỗ trợ đơn hàng',
                        Language::ENGLISH->value => 'Order support',
                        Language::CHINESE->value => '订单支持',
                    ],
                    'description' => [
                        Language::VIETNAMESE->value => 'Kiểm tra trạng thái, thay đổi hoặc xác nhận đơn hàng gần nhất.',
                        Language::ENGLISH->value => 'Check order status, changes, or the latest booking.',
                        Language::CHINESE->value => '检查订单状态、变更或最近的预约。',
                    ],
                ],
                [
                    'position' => 2,
                    'name' => [
                        Language::VIETNAMESE->value => 'Thanh toán và ví',
                        Language::ENGLISH->value => 'Payment & wallet',
                        Language::CHINESE->value => '支付和钱包',
                    ],
                    'description' => [
                        Language::VIETNAMESE->value => 'Hỗ trợ nạp tiền, rút tiền, giao dịch hoặc lỗi thanh toán.',
                        Language::ENGLISH->value => 'Handle deposit, withdrawal, transactions, or payment issues.',
                        Language::CHINESE->value => '处理充值、提现、交易或支付问题。',
                    ],
                ],
                [
                    'position' => 3,
                    'name' => [
                        Language::VIETNAMESE->value => 'Tài khoản và hồ sơ',
                        Language::ENGLISH->value => 'Account & profile',
                        Language::CHINESE->value => '账户和资料',
                    ],
                    'description' => [
                        Language::VIETNAMESE->value => 'Hỗ trợ đăng nhập, đổi mật khẩu, cập nhật hồ sơ.',
                        Language::ENGLISH->value => 'Help with login, password change, and profile updates.',
                        Language::CHINESE->value => '帮助登录、修改密码和更新资料。',
                    ],
                ],
                [
                    'position' => 4,
                    'name' => [
                        Language::VIETNAMESE->value => 'Khiếu nại dịch vụ',
                        Language::ENGLISH->value => 'Service complaint',
                        Language::CHINESE->value => '服务投诉',
                    ],
                    'description' => [
                        Language::VIETNAMESE->value => 'Ghi nhận phản hồi hoặc khiếu nại trong quá trình sử dụng dịch vụ.',
                        Language::ENGLISH->value => 'Record feedback or complaints during service usage.',
                        Language::CHINESE->value => '记录使用服务过程中的反馈或投诉。',
                    ],
                ],
                [
                    'position' => 5,
                    'name' => [
                        Language::VIETNAMESE->value => 'Tư vấn & Hỗ trợ',
                        Language::ENGLISH->value => 'Other support',
                        Language::CHINESE->value => '其他支持',
                    ],
                    'description' => [
                        Language::VIETNAMESE->value => 'Các vấn đề hỗ trợ khác không thuộc các danh mục trên.',
                        Language::ENGLISH->value => 'Other support issues not covered by the above categories.',
                        Language::CHINESE->value => '上述类别未涵盖的其他支持问题。',
                    ],
                ]
            ];

            foreach ($items as $item) {
                SupportCategory::query()->updateOrCreate(
                    ['position' => $item['position']],
                    [
                        'name' => $item['name'],
                        'description' => $item['description'],
                        'is_active' => true,
                    ]
                );
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}

