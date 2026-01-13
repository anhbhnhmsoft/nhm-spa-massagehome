<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AffiliateController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\PageController;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/affiliate/{referrerId}', [AffiliateController::class, 'handleAffiliateLink'])->name('affiliate.link');

Route::get('/{slug}', [PageController::class, 'show'])->name('page.show');
