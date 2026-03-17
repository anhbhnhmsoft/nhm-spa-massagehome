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
     * Kiểm tra ngôn ngữ có hợp lệ không.
     * @param string|null $language
     * @return bool
     */
    public static function checkLanguage(?string $language = null): bool
    {
        return in_array($language, [Language::VIETNAMESE->value, Language::ENGLISH->value, Language::CHINESE->value], true);
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
     * Lấy URL công khai cho tệp tin riêng tư. (có check quyền truy cập)
     * @param string $id
     * @return string
     */
    public static function getPrivateUrl(string $id): string
    {
        return route('file.user-file-private', ['id' => $id]);
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

    /**
     * Định dạng khoảng cách từ mét sang km hoặc m.
     * @param $meters
     * @return string
     */
    public static function formatDistanceMeter($meters): string
    {
        if (is_null($meters)) {
            return 'Chưa xác định';
        }

        if ($meters < 1000) {
            return round($meters) . ' m';
        }

        return rtrim(number_format($meters / 1000, 1), '.0') . ' km';
    }

    public static function isValidPhone($phone): bool
    {
        $phone = self::formatPhone($phone);
        return preg_match('/^84\d{9}$/', $phone);
    }

    /**
     * Tính khoảng cách giữa 2 tọa độ theo công thức Haversine
     * @param $lat1
     * @param $lon1
     * @param $lat2
     * @param $lon2
     * @return float|int
     */
    public static function getDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Xóa các file tạm đã upload khi gặp lỗi.
     * @param array{disk: string, path: string} $files
     * @return void
     */
    public static function cleanupFiles(array $files) {
        foreach ($files as $file) {
            Storage::disk($file['disk'])->delete($file['path']);
        }
    }

    /**
     * Định dạng giá tiền thành chuỗi có dấu chấm ngăn cách hàng nghìn và dấu ₫ ở cuối.
     * @param float $price
     * @return string
     */
    public static function formatPrice(float $price): string
    {
        return number_format($price, 0, ',', '.');
    }

    /**
     * Định dạng dữ liệu đa ngôn ngữ thành mảng có cấu trúc:
     * [
     *     'en' => 'English translation',
     *     'vi' => 'Tiếng Việt translation',
     *     ...
     * ]
     * @param array $data
     * @param string $fallback
     * @return array
     */
    public static function formatMultiLang(array $data, string $fallback = ''): array
    {
        $allTranslations = collect(Language::cases())->mapWithKeys(function ($lang) use ($data, $fallback) {
            return [
                $lang->value => $data[$lang->value] ?? $fallback
            ];
        })->toArray();
        return $allTranslations;
    }
}
