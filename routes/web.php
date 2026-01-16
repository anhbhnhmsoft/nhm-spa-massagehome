<?php

use App\Http\Controllers\API\AffiliateController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/affiliate/{referrerId}', [AffiliateController::class, 'handleAffiliateLink'])->name('affiliate.link');

Route::get('/{slug}', [PageController::class, 'show'])->name('page.show');


Route::prefix('zalo')->group(function () {
    // Zalo Access Token Initialization Routes
    Route::get('callback', [\App\Http\Controllers\Web\ZaloController::class, 'callback'])->name('zalo.callback');
});
