<?php

namespace App\Core\Helper;

use App\Core\Helper;
use App\Models\Coupon;

class CalculatePrice
{

    /**
     * Tính toán giá đặt lịch dịch vụ
     * @param float $price Giá gốc của dịch vụ
     * @param Coupon|null $coupon Mã giảm giá (nếu có)
     * @param float $pricePerKm Giá tiền/km
     * @param float $longitude Vĩ độ của khách hàng
     * @param float $latitude Kinh độ của khách hàng
     * @param float $ktvLongitude Vĩ độ của KTV
     * @param float $ktvLatitude Kinh độ của KTV
     * @return array{
     *     price: float, // Giá gốc của dịch vụ
     *     price_per_km: float, // Giá tiền/km
     *     distance: float, // Khoảng cách giữa khách hàng và KTV
     *     price_distance: float, // Giá di chuyển
     *     discount_coupon: float, // Giá trị giảm giá coupon
     *     final_price: float, // Giá cuối cùng (Total = Gía gốc + Giá di chuyển - Giá giảm giá coupon)
     * }
     */
    public static function calculateBookingPrice(
        float$price,
        ?Coupon $coupon,
        float $pricePerKm,
        float $longitude,
        float $latitude,
        float $ktvLongitude,
        float $ktvLatitude
    ): array
    {
        $discountCoupon = 0;
        // Nếu có coupon
        if ($coupon) {
            // Tính giá trị giảm giá dựa trên mã giảm giá và giá trị đơn hàng.
            $discountCoupon = self::couponDiscountAmount(
                coupon: $coupon,
                price: $price,
            );
        }

        // Tính khoảng cách giữa khách hàng và KTV
        $distance = Helper::getDistance(
            lat1: $latitude,
            lon1: $longitude,
            lat2: $ktvLatitude,
            lon2: $ktvLongitude
        );

        // Tính giá di chuyển
        $priceDistance = self::transportationCost(
            distance: $distance,
            pricePerKm: $pricePerKm
        );

        /**
         * Tính giá cuối cùng (Final Price)
         * Total = Gía gốc + Giá di chuyển - Giá giảm giá coupon
         */
        $finalPrice = self::totalBookingPrice(
            price: $price,
            priceDiscount: $discountCoupon,
            priceTransportation: $priceDistance,
        );

        return [
            'price' => (float)$price,
            'price_per_km' => (float)$pricePerKm,
            'distance' => (float)$distance,
            'price_distance' => (float)$priceDistance,
            'discount_coupon' => (float)$discountCoupon,
            'final_price' => $finalPrice,
        ];
    }

    /**
     * Tính giá trị tổng cộng của đặt lịch dịch vụ
     * Total = Gía gốc + Giá di chuyển - Giá giảm giá coupon
     * @param float $price
     * @param float $priceDiscount
     * @param float $priceTransportation
     * @return float
     */
    public static function totalBookingPrice(
        float $price,
        float $priceDiscount,
        float $priceTransportation,
    ): float
    {
        return $price + $priceTransportation - $priceDiscount;
    }

    /**
     * Tính giá trị giảm giá dựa trên mã giảm giá và giá trị đơn hàng.
     * @param Coupon $coupon
     * @param $price
     * @return float
     */
    public static function couponDiscountAmount(
        Coupon $coupon,
        $price,
    ): float {
        // Tính giá trị giảm giá trước khi kiểm tra max_discount
        if ($coupon->is_percentage) {
            $discountAmount = ($price * $coupon->discount_value) / 100;
        } else {
            // Đảm bảo discount không vượt quá giá trị đơn hàng
            $discountAmount = min($price, $coupon->discount_value);
        }
        // Đảm bảo discount không âm
        $discountAmount = max(0, $discountAmount);

        // Kiểm tra giá trị giảm tối đa không vượt quá giá trị tối đa của mã
        if ($coupon->max_discount !== null && $discountAmount > $coupon->max_discount) {
            $discountAmount = $coupon->max_discount;
        }
        return $discountAmount;
    }

    /**
     * Tính chi phí vận chuyển giữa 2 tọa độ
     * @param float $distance Khoảng cách giữa 2 tọa độ (km)
     * @param float $pricePerKm Giá tiền vận chuyển mỗi km
     * @return float
     */
    public static function transportationCost(
        float $distance,
        float $pricePerKm,
    ): float {

        $rawPrice = $pricePerKm * (float)$distance;

        // Làm tròn lên bội số 500
        return ceil($rawPrice / 500) * 500;
    }


    /**
     * Tính toán số tiền hoa hồng mà người giới thiệu sẽ nhận được.
     * @param float $price Giá dịch vụ (sau khi trừ giảm giá của KTV).
     * @param float $commissionPercent Tỷ lệ hoa hồng (ví dụ: 10, 20...).
     * @param float $minCommission Giá trị hoa hồng tối thiểu.
     * @param float $maxCommission Giá trị hoa hồng tối đa.
     * @param int $precision Độ chính xác làm tròn (mặc định là 0 để lấy số nguyên).
     * @return float
     */
    public static function calculatePriceAffiliate(float $price, float $commissionPercent, float $minCommission, float $maxCommission, int $precision = 0): float
    {
        // Tính số tiền hoa hồng mà người giới thiệu sẽ nhận được
        $amount = $price * ($commissionPercent / 100);
        // Clamp giá trị trong khoảng min/max
        $amount = max($minCommission, min($amount, $maxCommission));
        // Làm tròn số tiền hoa hồng
        return round($amount, $precision);
    }

    /**
     * Tính toán số tiền mà người giới thiệu sẽ nhận được.
     * @param float $price
     * @param float $discountRate
     * @param int $precision
     * @return float
     */
    public static function calculatePriceReferrer(float $price, float $discountRate, int $precision = 0): float
    {
        $discountRate = max(0, min(100, $discountRate));

        return round($price * ($discountRate / 100), $precision);
    }

    public static function calculateSystemMinus(float $price, float $discountRate = 0, int $precision = 0): float
    {
        // Đảm bảo tỷ lệ chiết khấu hợp lệ
        $discountRate = max(0, min(100, $discountRate));

        return round($price * ($discountRate / 100), $precision);
    }

    /**
     * Tính toán số tiền KTV thực nhận.
     * @param float $price Giá dịch vụ (sau khi trừ giảm giá của KTV).
     * @param float $discountRate Tỷ lệ chiết khấu hệ thống (ví dụ: 10, 20...).
     * @param int $precision Độ chính xác làm tròn (mặc định là 0 để lấy số nguyên).
     * @return float
     */
    public static function calculatePriceDiscountForKTV(float $price, float $discountRate, int $precision = 0): float
    {
        // Tính số tiền Hệ thống thu (Commission) và làm tròn trước
        $systemMinus = self::calculateSystemMinus($price, $discountRate, $precision);

        // KTV thực nhận = Tổng tiền - Phí sàn
        return $price - $systemMinus;
    }

}
