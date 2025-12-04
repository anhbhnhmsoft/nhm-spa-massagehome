<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ServiceController;
use App\Http\Controllers\API\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::middleware('set-locale')->group(function () {

    // Authenticate routes
    Route::prefix('auth')->group(function () {
        // Guest middleware
        Route::middleware(['throttle:5,1', 'guest:sanctum'])->group(function () {
            // Xác thực đăng nhập hay đăng ký.
            Route::post('authenticate', [AuthController::class, 'authenticate']);
            // Đăng nhập vào hệ thống.
            Route::post('login', [AuthController::class, 'login']);
            // Kiểm tra OTP đăng ký và đăng ký tài khoản.
            Route::post('verify-otp-register', [AuthController::class, 'verifyOtpRegister']);
            // Gửi lại OTP đăng ký.
            Route::post('resend-otp-register', [AuthController::class, 'resendOtpRegister']);
            // Đăng ký tài khoản mới.
            Route::post('register', [AuthController::class, 'register']);
        });

        // Auth middleware
        Route::middleware(['auth:sanctum'])->group(function () {
            // Lấy thông tin hồ sơ người dùng.
            Route::get('profile', [AuthController::class, 'getProfile']);
            // Cập nhật ngôn ngữ hệ thống.
            Route::post('set-language', [AuthController::class, 'setLanguage']);
        });
    });

    Route::prefix('user')->group(function () {
        // router không cần auth

        // Lấy danh sách KTV
        Route::get('list-ktv', [UserController::class, 'listKtv']);

        // router cần auth
        Route::middleware(['auth:sanctum'])->group(function () {
            // Lấy thông tin KTV
            Route::get('ktv/{id}', [UserController::class, 'getKtv'])->where('id', '[0-9]+');
        });
    });

    // Service routes
    Route::prefix('service')->group(function () {
        // router không cần auth

        // Lấy danh sách dịch vụ
        Route::get('list-category', [ServiceController::class, 'listCategory']);

        // Lấy danh sách dịch vụ
        Route::get('list', [ServiceController::class, 'listServices']);
    });

});



