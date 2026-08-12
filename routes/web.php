<?php

use App\Http\Controllers\API\AffiliateController;
use App\Http\Controllers\Web\SalePortalCustomerController;
use App\Http\Controllers\Web\SalePortalSupportController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\PageController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

// Locale switching route
Route::get('/locale/{locale}', function ($locale) {
    if (in_array($locale, ['vi', 'en', 'zh', 'cn'])) {
        // Map 'zh' to 'cn' for consistency
        $locale = $locale === 'zh' ? 'cn' : $locale;
        session(['locale' => $locale]);
    }
    return redirect()->back();
})->name('locale.switch');

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/affiliate/{referrerId}', [AffiliateController::class, 'handleAffiliateLink'])->name('affiliate.link');

Route::prefix('sale-api')
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->group(function () {
        Route::post('login', [SalePortalSupportController::class, 'login']);
        Route::post('logout', [SalePortalSupportController::class, 'logout'])->middleware('auth:web');
        Route::middleware(['auth:web', 'check-admin-role:customer_support'])->group(function () {
            Route::get('me', [SalePortalSupportController::class, 'me']);
            Route::get('socket-token', [SalePortalSupportController::class, 'socketToken']);
            Route::post('heartbeat', [SalePortalSupportController::class, 'heartbeat']);
            Route::get('queue-stats', [SalePortalSupportController::class, 'queueStats']);

            Route::get('customers', [SalePortalCustomerController::class, 'index']);
            Route::get('tickets', [SalePortalSupportController::class, 'inbox']);
            Route::post('tickets/initiate', [SalePortalSupportController::class, 'initiateChat']);
            Route::get('tickets/{id}', [SalePortalSupportController::class, 'detail'])->where('id', '[0-9]+');
            Route::get('messages/{id}', [SalePortalSupportController::class, 'messages'])->where('id', '[0-9]+');
            Route::post('tickets/{id}/claim', [SalePortalSupportController::class, 'claim'])->where('id', '[0-9]+');
            Route::post('tickets/{id}/close', [SalePortalSupportController::class, 'close'])->where('id', '[0-9]+');
            Route::post('messages', [SalePortalSupportController::class, 'sendMessage']);
            Route::post('seen', [SalePortalSupportController::class, 'seen']);
        });
        Route::post('tickets/{id}/reopen', [SalePortalSupportController::class, 'reopen'])
            ->middleware('auth:web')->where('id', '[0-9]+');
    });

Route::get('/{slug}', [PageController::class, 'show'])->name('page.show');


Route::prefix('zalo')->group(function () {
    // Zalo Access Token Initialization Routes
    Route::get('callback', [\App\Http\Controllers\Web\ZaloController::class, 'callback'])->name('zalo.callback');
});
