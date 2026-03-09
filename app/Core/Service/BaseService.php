<?php

namespace App\Core\Service;

use App\Core\LogHelper;
use Closure;
use Illuminate\Support\Facades\DB;

abstract class BaseService
{
    public function __construct()
    {

    }

    /**
     * Thực thi một hành động với cơ chế transaction (nếu cần).
     * @param callable $callback
     * @param bool $useTransaction
     * @param string|null $actionName
     * @param callable|null $catchCallback
     * @param bool $logServiceError
     * @param callable|null $afterCommitCallback
     * @return ServiceReturn
     * @throws \Throwable
     */
    protected function execute(
        callable $callback,
        bool     $useTransaction = false,
        ?string  $actionName = null,
        callable $catchCallback = null,
        bool     $logServiceError = false,
        callable $afterCommitCallback = null
    ): ServiceReturn {
        if ($useTransaction) {
            DB::beginTransaction();
        }

        try {
            $result = $callback();

            if ($useTransaction) {
                if ($afterCommitCallback) {
                    // Đăng ký callback chạy SAU KHI commit thành công
                    DB::afterCommit($afterCommitCallback);
                }
                DB::commit();
            }

            return $result instanceof ServiceReturn ? $result : ServiceReturn::success($result);

        } catch (\Throwable $e) {
            if ($useTransaction) {
                DB::rollBack();
            }

            if ($catchCallback) {
                $catchCallback($e);
            }

            // Tự động xác định Context lỗi
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $caller = $trace[1]['function'] ?? 'unknown';
            $context = $actionName ?? static::class . "::" . $caller;

            // Phân loại lỗi để Log và Return
            if ($e instanceof ServiceException) {
                if ($logServiceError) {
                    LogHelper::error("[{$context}] Service Error: " . $e->getMessage(), $e);
                }
                return ServiceReturn::error($e->getMessage(), $e);
            }

            // Lỗi hệ thống nghiêm trọng
            LogHelper::error("[{$context}] Critical Error: " . $e->getMessage(), $e);

            return ServiceReturn::error(__("common_error.server_error"));
        }
    }
}
