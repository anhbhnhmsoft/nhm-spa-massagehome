<?php

namespace App\Services\Validator;

use App\Core\Helper;
use App\Core\Service\ServiceException;
use App\Models\Wallet;

class WalletValidator
{
    /**
     * Kiểm tra số dư trong ví có đủ để thanh toán không
     * @param Wallet $wallet
     * @param float $price
     * @param float $priceDistance
     * @param float $couponDiscount
     * @return void
     * @throws ServiceException
     */
    public function validateBookingBalance(Wallet $wallet, float $price, float $priceDistance, float $couponDiscount = 0)
    {
        if ($price > $wallet->balance) {
            throw new ServiceException(
                message: __("booking.error.user_not_enough_money", [
                    'balance' => Helper::formatPrice($wallet->balance),
                    'price' => Helper::formatPrice($price),
                    'coupon_discount' => Helper::formatPrice($couponDiscount),
                    'price_move' => Helper::formatPrice($priceDistance),
                ])
            );
        }
    }

}
