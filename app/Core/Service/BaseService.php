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
     * Hàm dùng để thực thi execute an toàn
     * @param callable $callback
     * @param bool $useTransaction
     * @param string|null $actionName
     * @param callable|null $catchCallback
     * @return ServiceReturn
     * @throws \Throwable
     */
    protected function execute(callable $callback, bool $useTransaction = false, ?string $actionName = null, callable $catchCallback = null): ServiceReturn
    {
        if ($useTransaction) DB::beginTransaction();

        try {
            $result = $callback();

            if ($useTransaction) DB::commit();

            return $result instanceof ServiceReturn ? $result : ServiceReturn::success($result);
        } catch (ServiceException $e) {
            if ($useTransaction) DB::rollBack();
            // Gọi hàm catchCallback nếu có
            if ($catchCallback) {
                $catchCallback($e);
            }
            return ServiceReturn::error($e->getMessage(), $e);
        } catch (\Throwable $e) {
            if ($useTransaction) DB::rollBack();
            // Gọi hàm catchCallback nếu có
            // Tự động định danh vị trí lỗi
            if ($catchCallback) {
                $catchCallback($e);
            }
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
            $caller = $trace[1]['function'] ?? 'unknown';
            $context = $actionName ?? static::class . "::" . $caller;

            LogHelper::error("[{$context}] Error: " . $e->getMessage(), $e);

            return ServiceReturn::error(__("common_error.server_error"));
        }
    }
}
