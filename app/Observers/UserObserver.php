<?php

namespace App\Observers;

use App\Models\Coupon;
use App\Models\User;
use Illuminate\Support\Str;

class UserObserver
{
    /**
     * Handle the User "created" event.
     * Tặng mã giảm giá Welcome khi user đăng ký
     */
    public function created(User $user): void
    {
        // Tìm mẫu coupon chào mừng (Code: WELCOME)
        $template = Coupon::where('code', 'WELCOME')
            ->where('is_active', true)
            ->whereNull('user_id')
            ->first();

        if ($template) {
            // Tạo bản sao riêng cho user mới này
            $newCoupon = $template->replicate();
            $newCoupon->code = 'WELCOME-' . $user->id . '-' . Str::upper(Str::random(4));
            $newCoupon->user_id = $user->id;
            $newCoupon->start_at = now();
            // Hạn dùng 7 ngày kể từ lúc đăng ký
            $newCoupon->end_at = now()->addDays(7);
            $newCoupon->used_count = 0;
            $newCoupon->usage_limit = 1;
            $newCoupon->save();

            // Tự động cho vào ví
            $user->collectionCoupons()->syncWithoutDetaching([
                $newCoupon->id => ['is_used' => false]
            ]);
        }
    }
}
