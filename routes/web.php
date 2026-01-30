<?php

use App\Http\Controllers\API\AffiliateController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\PageController;
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

Route::get('/{slug}', [PageController::class, 'show'])->name('page.show');


Route::prefix('zalo')->group(function () {
    // Zalo Access Token Initialization Routes
    Route::get('callback', [\App\Http\Controllers\Web\ZaloController::class, 'callback'])->name('zalo.callback');
});
