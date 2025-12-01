<?php

namespace App\Core;

use Illuminate\Support\Facades\Log;

/**
 * Class LogHelper
 * Lớp tiện ích (helper) để ghi log ra các kênh file riêng biệt
 * đã được định nghĩa trong config/logging.php.
 */
class LogHelper
{
    /**
     * Ghi log HÀNH ĐỘNG (Nghiệp vụ) vào kênh 'actions'.
     * (Sẽ được lưu vào storage/logs/actions-YYYY-MM-DD.log)
     *
     * @param string $message
     * @param array $context
     */
    public static function action(string $message, array $context = []): void
    {
        // Ghi vào kênh 'actions' với level 'info'
        Log::channel('actions')->info($message, $context);
    }

    /**
     * Ghi log DEBUG (Dành cho dev) vào kênh 'console'.
     * (Sẽ được lưu vào storage/logs/console-YYYY-MM-DD.log)
     *
     * @param string $message
     * @param array $context
     */
    public static function debug(string $message, array $context = []): void
    {
        // Ghi vào kênh 'console' với level 'debug'
        Log::channel('console')->debug($message, $context);
    }

    /**
     * Ghi log LỖI HỆ THỐNG vào kênh 'errors'.
     * (Sẽ được lưu vào storage/logs/errors-YYYY-MM-DD.log)
     *
     * @param string $message
     * @param \Throwable|null $ex (Exception)
     * @param array $context
     */
    public static function error(string $message, ?\Throwable $ex = null, array $context = []): void
    {
        if ($ex) {
            $context['exception_summary'] = [
                'message' => $ex->getMessage(),
                'file'    => $ex->getFile(),
                'line'    => $ex->getLine(),
            ];
        }
        // Ghi vào kênh 'errors' với level 'error'
        Log::channel('errors')->error($message, $context);
    }
}
