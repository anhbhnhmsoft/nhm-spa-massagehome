<?php

namespace App\Core;

use App\Enums\Language;
use App\Enums\PaymentType;
use App\Enums\UserRole;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class Helper
{

    /**
     * Tạo ID duy nhất dựa trên timestamp hiện tại.
     * @return int
     */
    public static function getTimestampAsId(): int
    {
        // Get microtime float
        $microFloat = microtime(true);
        $microTime = Carbon::createFromTimestamp($microFloat);
        $formatted = $microTime->format('ymdHisu');
        usleep(100);
        return (int)$formatted;
    }

    /**
     * Tạo mô tả ngắn cho thanh toán.
     * @param PaymentType $paymentType
     * @return string
     */
    public static function createDescPayment(PaymentType $paymentType): string
    {
        return match ($paymentType) {
            PaymentType::QR_BANKING => "QRBK" . self::getTimestampAsId(),
            PaymentType::ZALO_PAY => "ZLPY" . self::getTimestampAsId(),
            PaymentType::MOMO_PAY => "MMPY" . self::getTimestampAsId(),
            PaymentType::BY_POINTS => "BYP" . self::getTimestampAsId(),
            PaymentType::WITHDRAWAL => "WDL" . self::getTimestampAsId(),
            PaymentType::REFUND => "RFD" . self::getTimestampAsId(),
            PaymentType::WECHAT => "WCT" . self::getTimestampAsId(),
            default => "UNKNOWN" . self::getTimestampAsId(),
        };
    }

    /**
     * Tạo mã tham gia mới cho người dùng dựa trên vai trò.
     * @param UserRole $role
     * @return string
     */
    public static function generateReferCodeUser(UserRole $role): string
    {
        $fix = match ($role) {
            UserRole::ADMIN => 'ADM-',
            UserRole::AGENCY => 'AGN-',
            UserRole::KTV => 'KTV-',
            UserRole::CUSTOMER => 'CST-',
        };
        return $fix . self::generateReferCode();
    }

    /**
     * Tạo mã tham gia ngẫu nhiên 8 ký tự in hoa.
     * @param int|null $length
     * @return string
     */
    public static function generateReferCode(?int $length = 8): string
    {
        return strtoupper(substr(Str::uuid()->toString(), 0, $length));
    }

    /**
     * Tạo token ngẫu nhiên 60 ký tự.
     * @return string
     */
    public static function generateTokenRandom(): string
    {
        return Str::random(60);
    }

    /**
     * Kiểm tra ngôn ngữ có hợp lệ không.
     * @param string|null $language
     * @return bool
     */
    public static function checkLanguage(?string $language = null): bool
    {
        return in_array($language, [Language::VIETNAMESE->value, Language::ENGLISH->value, Language::CHINESE->value], true);
    }

    public static function FileUrl(string $path): string
    {
        return route('file_url_render', ['path' => $path]);
    }

    /**
     * Kiểm tra thiết bị có phải là thiết bị di động không.
     * @param string $userAgent
     * @return bool
     */
    public static function isMobileDevice($userAgent)
    {
        return preg_match('/(android|iphone|ipad|mobile)/i', $userAgent);
    }

    /**
     * Lấy URL công khai cho tệp tin.
     * @param string $path
     * @return string
     */
    public static function getPublicUrl(string $path): string
    {
        return Storage::disk('public')->url($path);
    }

    /**
     * Xử lý dữ liệu đa ngôn ngữ.
     * @param array $source
     * @param string $field
     * @return array
     */
    public static function multilingualPayload(array $source, string $field): array
    {
        $data = $source[$field] ?? [];
        // Tìm giá trị fallback (lấy giá trị đầu tiên không rỗng trong mảng)
        $fallback = '';
        foreach ($data as $val) {
            if (!empty($val)) {
                $fallback = $val;
                break;
            }
        }
        return [
            Language::VIETNAMESE->value => !empty($data[Language::VIETNAMESE->value]) ? $data[Language::VIETNAMESE->value] : $fallback,
            Language::ENGLISH->value    => !empty($data[Language::ENGLISH->value]) ? $data[Language::ENGLISH->value] : $fallback,
            Language::CHINESE->value    => !empty($data[Language::CHINESE->value]) ? $data[Language::CHINESE->value] : $fallback,
        ];
    }

    /**
     * Helper xóa file. Hỗ trợ string, JSON string, hoặc array.
     * @param string|array|null $path
     * @param string $disk
     * @return void
     */
    public static function deleteFile(string|array|null $path, string $disk = 'public'): void
    {
        if (empty($path)) {
            return;
        }

        // Nếu là array, duyệt đệ quy
        if (is_array($path)) {
            foreach ($path as $p) {
                self::deleteFile($p, $disk);
            }
            return;
        }

        // Nếu là chuỗi JSON, log decode và gọi đệ quy
        if (is_string($path) && Str::isJson($path)) {
            $decoded = json_decode($path, true);
            if (is_array($decoded)) {
                self::deleteFile($decoded, $disk);
                return;
            }
        }

        // Xóa file (xử lý xóa storage)
        if (is_string($path)) {
            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
            }
        }
    }


    /**
     * Tính toán số tiền hệ thống phải trừ đi.
     * @param float $price
     * @param float $discountRate
     * @param int $precision
     * @return float
     */
    public static function calculateSystemMinus(float $price, float $discountRate = 0, int $precision = 0): float
    {
        // Đảm bảo tỷ lệ chiết khấu hợp lệ
        $discountRate = max(0, min(100, $discountRate));

        return round($price * ($discountRate / 100), $precision);
    }

    /**
     * Tính toán số tiền KTV thực nhận.
     * * @param float $price Giá dịch vụ (sau khi trừ giảm giá của KTV).
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
        $amount = $price * (100 - $commissionPercent) / 100;
        // Clamp giá trị trong khoảng min/max
        $amount = max($minCommission, min($amount, $maxCommission));
        // Làm tròn số tiền hoa hồng
        return round($amount, $precision);
    }

    /**
     * Tính toán số tiền mà người dùng sẽ nhận được khi rút tiền.
     * @param float $amount Số tiền Point cần rút.
     * @param float $exchangeRate Tỷ giá đổi từ Point sang VND.
     * @param float $feePercent Phí rút tiền (ví dụ: 1%...).
     * @return array{withdraw_money: float, fee_withdraw: float}
     */
    public static function calculateWithdrawAmount(float $amount, float $exchangeRate, float $feePercent): array
    {
        // Quy đổi Point ra tiền mặt (Gross)
        $grossAmount = $amount * $exchangeRate;

        // Tính số tiền phí
        $feeAmount = ($grossAmount * $feePercent) / 100;

        // Số tiền thực nhận (Phải dùng floor để khớp với frontend)
        $withdrawMoney = floor($grossAmount - $feeAmount);

        return [
            'withdraw_money' => $withdrawMoney,
            'fee_withdraw' => $feeAmount,
        ];
    }


    public static function formatPhone($phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($phone, '0')) {
            $phone = '84' . substr($phone, 1);
        }
        if (!str_starts_with($phone, '84')) {
            $phone = '84' . $phone;
        }
        return $phone;
    }

    public static function isValidPhone($phone): bool
    {
        $phone = self::formatPhone($phone);
        return preg_match('/^84\d{9}$/', $phone);
    }

    public static function getDistance($lat1, $lon1, $lat2, $lon2)
    {
        $R = 6371;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $R * $c;
    }
}
