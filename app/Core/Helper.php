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
        $fallback = null;
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
}
