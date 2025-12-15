<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AffiliateController;

Route::get('/', function () {
    var_dump('hello world');
})->name('home');

Route::get('/affiliate/{referrerId}', [AffiliateController::class, 'handleAffiliateLink'])->name('affiliate.link');
