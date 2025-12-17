<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CommercialController;
use App\Http\Controllers\API\FileController;
use App\Http\Controllers\API\BookingController;
use App\Http\Controllers\API\LocationController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\ServiceController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\AffiliateController;
use Illuminate\Support\Facades\Route;



Route::middleware('set-api-locale')->group(function () {
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
            // Cập nhật heartbeat cho user.
            Route::post('heartbeat', [AuthController::class, 'heartbeat']);
            // Cập nhật thông tin thiết bị.
            Route::post('set-device', [AuthController::class, 'setDevice']);
            // edit avatar
            Route::post('edit-avatar', [AuthController::class, 'editAvatar']);
            // delete avatar
            Route::delete('delete-avatar', [AuthController::class, 'deleteAvatar']);
            // Cập nhật thông tin hồ sơ người dùng.
            Route::post('edit-profile', [AuthController::class, 'editProfile']);
            // Đăng xuất khỏi hệ thống.
            Route::post('logout', [AuthController::class, 'logout']);
        });
    });

    Route::prefix('location')->middleware(['auth:sanctum'])->group(function () {
        /**
         * autocomplete search location
         */
        Route::get('search', [LocationController::class, 'search'])->middleware(['throttle:5,0.3']);
        /**
         * detail location
         */
        Route::get('detail', [LocationController::class, 'detail'])->middleware(['throttle:5,0.2']);
        /**
         * list provinces
         */
        Route::get('provinces', [LocationController::class, 'listProvinces']);
        /**
         * list address
         */
        Route::get('address', [UserController::class, 'listAddress']);
        /**
         * save address
         */
        Route::post('save', [UserController::class, 'saveAddress']);
        /**
         * edit address
         */
        Route::put('edit/{id}', [UserController::class, 'editAddress'])->where('id', '[0-9]+');
        /**
         * delete address
         */
        Route::delete('delete/{id}', [UserController::class, 'deleteAddress'])->where('id', '[0-9]+');
    });

    Route::prefix('user')->group(function () {
        /**
         * router không cần auth
         */
        // Lấy danh sách KTV
        Route::get('list-ktv', [UserController::class, 'listKtv']);

        // Lấy thông tin chi tiết KTV
        Route::get('ktv/{id}', [UserController::class, 'detailKtv'])->where('id', '[0-9]+');

        // Đăng ký cộng tác viên/đối tác
        Route::post('register-agency', [UserController::class, 'registerAgency'])->middleware(['throttle:5,1']);

        /**
         * router cần auth
         */
        Route::middleware(['auth:sanctum'])->group(function () {
            // Lấy thông tin chi tiết hồ sơ người dùng
            Route::get('dashboard-profile', [UserController::class, 'dashboardProfile']);
        });
    });

    Route::prefix('service')->group(function () {
        /**
         * router không cần auth
         */
        // Lấy danh sách dịch vụ
        Route::get('list-category', [ServiceController::class, 'listCategory']);

        // Lấy danh sách dịch vụ
        Route::get('list', [ServiceController::class, 'listServices']);

        // Lấy thông tin chi tiết dịch vụ
        Route::get('detail/{id}', [ServiceController::class, 'detailService'])->where('id', '[0-9]+');

        // Lấy danh sách mã giảm giá
        Route::get('list-coupon', [ServiceController::class, 'listCoupon']);

        /**
         * router cần auth
         */
        Route::middleware(['auth:sanctum'])->group(function () {
            // Đặt lịch dịch vụ
            Route::post('booking', [ServiceController::class, 'booking']);
            // Lấy danh sách lịch đã đặt hôm nay
            Route::get('today-booked/{id}', [ServiceController::class, 'getTodayBookedCustomers'])->where('id', '[0-9]+');

        });
    });

    Route::prefix('booking')->group(function () {
        Route::middleware(['auth:sanctum'])->group(function () {
            Route::get('list', [BookingController::class, 'listBooking']);
        });

        Route::get('{bookingId}', [BookingController::class, 'checkBooking']);
    });

    Route::prefix('payment')->group(function () {
        // router không cần auth
        Route::prefix('webhook')->group(function () {
            // Xử lý webhook PayOS
            Route::post('payos', [PaymentController::class, 'handleWebhookPayOs']);
        });

        // Lấy danh sách dịch vụ
        Route::middleware(['auth:sanctum'])->group(function () {
            Route::get('transactions', [PaymentController::class, 'listTransaction']);
            Route::get('wallet', [PaymentController::class, 'userWallet']);
            Route::get('config', [PaymentController::class, 'configPayment']);
            Route::post('deposit', [PaymentController::class, 'deposit']);
            Route::get('check-transaction', [PaymentController::class, 'checkTransaction']);
        });
    });

    Route::prefix('file')->group(function () {
        Route::get('user/{path}', [FileController::class, 'getUserFile'])->name('file_url_render')->where('path', '.*');
        Route::post('upload', [FileController::class, 'upload']);
        Route::get('commercial/{path}', [FileController::class, 'getCommercialFile'])->where('path', '.*')->name('file.commercial');
    });

    Route::prefix('notification')->middleware(['auth:sanctum'])->group(function () {
        // Lấy danh sách notifications
        Route::get('list', [NotificationController::class, 'list']);
        // Lấy chi tiết notification
        Route::get('detail/{id}', [NotificationController::class, 'detail'])->where('id', '[0-9]+');
        // Đánh dấu đã đọc
        Route::put('read/{id}', [NotificationController::class, 'markAsRead'])->where('id', '[0-9]+');
        // Lấy số lượng chưa đọc
        Route::get('unread-count', [NotificationController::class, 'unreadCount']);
    });

    Route::prefix('affiliate')->group(function () {
        Route::get('match', [AffiliateController::class, 'matchAffiliate']);
    });
    Route::prefix('commercial')->group(function () {
        // Lấy danh sách banner cho homepage
        Route::get('banners', [CommercialController::class, 'getBanner']);
        // Lấy danh sách coupon ads cho homepage
        Route::get('coupons', [CommercialController::class, 'getCouponAds']);
        // Lấy danh sách coupon ads cho homepage
        Route::middleware(['auth:sanctum'])->group(function () {
            Route::post('collect-coupons', [CommercialController::class, 'collectCouponAds']);
        });
    });
});
