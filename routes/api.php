<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CommercialController;
use App\Http\Controllers\API\FileController;
use App\Http\Controllers\API\BookingController;
use App\Http\Controllers\API\KTVController;
use App\Http\Controllers\API\LocationController;
use App\Http\Controllers\API\AgencyController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\ServiceController;
use App\Http\Controllers\API\ChatController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\AffiliateController;
use App\Http\Controllers\API\ConfigController;
use App\Http\Controllers\API\ReviewController;
use App\Http\Controllers\API\WithdrawController;
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
            // Khóa tài khoản
            Route::post('lock-account', [AuthController::class, 'lockAccount']);
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

        /**
         * router cần auth
         */
        Route::middleware(['auth:sanctum'])->group(function () {
            // Lấy thông tin chi tiết hồ sơ người dùng
            Route::get('dashboard-profile', [UserController::class, 'dashboardProfile']);
            // Lấy thông tin chi tiết KTV
            Route::get('ktv/{id}', [UserController::class, 'detailKtv'])->where('id', '[0-9]+');
            // User hiện tại đăng ký làm đối tác
            Route::post('apply-partner', [UserController::class, 'applyPartner'])->middleware(['throttle:5,1']);
            // Lấy danh sách KTV được quản lý bởi Agency hoặc KTV
            Route::get('list-manage-ktv', [UserController::class, 'listKtvManager'])
                ->middleware(['check-role:agency,ktv']); // Chỉ cho phép Agency hoặc KTV quản lý
        });
    });

    Route::prefix('service')->group(function () {
        /**
         * router không cần auth
         */
        // Lấy danh sách dịch vụ
        Route::get('list-category', [ServiceController::class, 'listCategory']);

        /**
         * router cần auth
         */
        Route::middleware(['auth:sanctum'])->group(function () {
            // Lấy danh sách dịch vụ
            Route::get('list', [ServiceController::class, 'listServices']);
            // Lấy thông tin chi tiết dịch vụ
            Route::get('detail/{id}', [ServiceController::class, 'detailService'])->where('id', '[0-9]+');
            // Đặt lịch dịch vụ
            Route::post('booking', [ServiceController::class, 'booking'])
                ->middleware(['check-role:customer']); // Chỉ cho phép Customer đặt lịch
            // Lấy danh sách lịch đã đặt hôm nay
            Route::get('today-booked/{id}', [ServiceController::class, 'getTodayBookedCustomers'])->where('id', '[0-9]+');
            // Lấy danh sách mã giảm giá
            Route::get('list-coupon', [ServiceController::class, 'listCoupon']);
            // Lấy danh sách mã giảm giá của người dùng
            Route::get('my-list-coupon', [ServiceController::class, 'myListCoupon']);
            // Tạo đánh giá cho booking dịch vụ
            Route::post('review', [ReviewController::class, 'create'])->middleware(['throttle:10,1']);
            // Lấy danh sách đánh giá của dịch vụ
            Route::get('list-review', [ReviewController::class, 'listReview']);
        });
    });

    Route::prefix('booking')->group(function () {
        // router cần auth
        Route::middleware(['auth:sanctum'])->group(function () {
            // Lấy danh sách lịch đặt
            Route::get('list', [BookingController::class, 'listBooking']);
            // kiểm tra trạng thái booking
            Route::get('{bookingId}', [BookingController::class, 'checkBooking']);
            // Lấy thông tin chi tiết lịch đặt
            Route::get('detail/{id}', [BookingController::class, 'detailBooking'])->where('id', '[0-9]+');

            // KTV mới có thể hủy và hoàn thành booking
            Route::middleware(['check-role:ktv'])->group(function () {
                // Hủy lịch đặt
                Route::post('cancel', [BookingController::class, 'cancelBooking']);
                // Xác nhận hoàn thành lịch đặt
                Route::post('finish', [BookingController::class, 'finishBooking']);
            });
        });
    });

    Route::prefix('payment')->group(function () {
        // router không cần auth
        Route::prefix('webhook')->group(function () {
            // Xử lý webhook PayOS
            Route::post('payos', [PaymentController::class, 'handleWebhookPayOs']);
        });

        // router cần auth
        Route::middleware(['auth:sanctum'])->group(function () {
            // Lấy danh sách giao dịch
            Route::get('transactions', [PaymentController::class, 'listTransaction']);
            // Lấy ví người dùng
            Route::get('wallet', [PaymentController::class, 'userWallet']);
            // Lấy cấu hình thanh toán
            Route::get('config', [PaymentController::class, 'configPayment']);
            // Nạp tiền vào ví người dùng
            Route::post('deposit', [PaymentController::class, 'deposit']);
            // Kiểm tra trạng thái giao dịch
            Route::get('check-transaction', [PaymentController::class, 'checkTransaction']);
            // Lấy danh sách ngân hàng hỗ trợ rút tiền
            Route::get('bank-info', [PaymentController::class, 'getBank']);
            //Lấy thông tin tài khoản rút tiền
            Route::get('info-withdraw', [WithdrawController::class, 'getWithdrawInfo']);
            //Tạo thông tin tài khoản rút tiền
            Route::post('info-withdraw', [WithdrawController::class, 'createWithdrawInfo']);
            // Xóa thông tin tài khoản rút tiền
            Route::delete('info-withdraw/{id}', [WithdrawController::class, 'deleteWithdrawInfo']);
            //Yêu cầu rút tiền
            Route::post('request-withdraw', [WithdrawController::class, 'requestWithdraw']);
        });
    });

    Route::prefix('file')->group(function () {
        Route::get('contract', [FileController::class, 'getContract']);
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
        Route::middleware(['auth:sanctum'])->group(function () {
            // Lấy thông tin cấu hình affiliate
            Route::get('config', [ConfigController::class, 'getConfigAffiliate']);
            // Lấy danh sách người dùng được giới thiệu
            Route::get('list-referred', [AffiliateController::class, 'listReferred']);
        });
    });

    Route::prefix('commercial')->group(function () {
        // Lấy danh sách banner cho homepage
        Route::get('banners', [CommercialController::class, 'getBanner']);
        // Lấy danh sách coupon ads cho homepage
        Route::get('coupons', [CommercialController::class, 'getCouponAds']);
        // Collect coupon ads
        Route::post('collect-coupon/{couponId}', [CommercialController::class, 'collectCouponAds'])->where('couponId', '[0-9]+');
    });

    Route::prefix('chat')->middleware(['auth:sanctum'])->group(function () {
        // Tạo/lấy phòng chat giữa customer (user hiện tại) và KTV
        Route::post('room', [ChatController::class, 'createOrGetRoom']);
        // Lấy danh sách tin nhắn theo room_id (paginate)
        Route::get('messages/{roomId}', [ChatController::class, 'listMessages'])->where('roomId', '[0-9]+');
        // Gửi tin nhắn trong room (lưu DB + publish realtime)
        Route::post('message', [ChatController::class, 'sendMessage']);
        // Đánh dấu xem tin nhắn đã đọc
        Route::post('seen', [ChatController::class, 'seenMessage']);

        Route::middleware(['check-role:ktv'])->group(function () {
            // Lấy danh sách phòng chat KTV
            Route::get('ktv-conversations', [ChatController::class, 'listKtvConversation']);
        });
    });

    Route::prefix('review')->middleware(['auth:sanctum'])->group(function () {
        // Tạo đánh giá cho booking dịch vụ
        Route::get('list', [ReviewController::class, 'listReview']);
    });

    Route::prefix('config')->group(function () {
        // Lấy thông tin cấu hình hỗ trợ
        Route::get('support-channels', [ConfigController::class, 'getSupportChannels']);
    });

    // Chỉ dành cho KTV
    Route::prefix('ktv')->middleware(['auth:sanctum', 'check-role:ktv'])->group(function () {
        // Lấy thông tin dashboard profile của user hiện tại
        Route::get('dashboard', [KTVController::class, 'dashboard']);
        // Lấy danh sách booking của KTV
        Route::get('list-booking', [KTVController::class, 'listBooking']);
        // Lấy tất cả categories
        Route::get('all-categories', [KTVController::class, 'allCategories']);
        // Lấy giá của từng category
        Route::get('category-price/{id}', [KTVController::class, 'categoryPrice'])->where('id', '[0-9]+');
        // Lấy danh sách service của KTV
        Route::get('list-service', [KTVController::class, 'listService']);
        // Thêm service mới
        Route::post('add-service', [KTVController::class, 'addService']);
        // Cập nhật service
        Route::post('update-service/{id}', [KTVController::class, 'updateService'])->where('id', '[0-9]+');
        // Lấy thông tin chi tiết service
        Route::get('detail-service/{id}', [KTVController::class, 'detailService'])->where('id', '[0-9]+');
        // Xóa service
        Route::delete('delete-service/{id}', [KTVController::class, 'deleteService'])->where('id', '[0-9]+');
        // Bắt đầu dịch vụ
        Route::post('start-booking', [BookingController::class, 'startBooking']);
        // tổng hợp thu nhập
        Route::get('total-income', [KTVController::class, 'totalIncome']);
        // lấy profile ktv ( hình ảnh )
        Route::get('profile', [KTVController::class, 'profile']);
        // cập nhật profile ktv
        Route::post('edit-profile-ktv', [KTVController::class, 'editProfileKtv']);
        // upload ảnh ktv
        Route::post('upload-ktv-images', [KTVController::class, 'uploadKtvImages']);
        // xóa ảnh ktv
        Route::delete('delete-ktv-image/{id}', [KTVController::class, 'deleteKtvImage'])->where('id', '[0-9]+');
        // Lấy thông tin lịch làm việc của KTV
        Route::get('config-schedule', [KTVController::class, 'getConfigSchedule']);
        // Cập nhật thông tin lịch làm việc của KTV
        Route::post('config-schedule', [KTVController::class, 'editConfigSchedule']);
        // Link KTV to Referrer via QR
        Route::post('link-referrer', [KTVController::class, 'linkReferrer']);
    });

    // Dành cho agency
    Route::prefix('agency')->middleware(['auth:sanctum'])->group(function () {
        // Lấy thông tin dashboard profile của user hiện tại
        Route::get('manage-ktv', [AgencyController::class, 'listKtv']);
    });
});
