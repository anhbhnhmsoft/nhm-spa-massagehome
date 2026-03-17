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
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\ServiceController;
use App\Http\Controllers\API\ChatController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\AffiliateController;
use App\Http\Controllers\API\ConfigController;
use App\Http\Controllers\API\WithdrawController;
use Illuminate\Support\Facades\Route;


Route::middleware(['set-api-locale', 'update-last-active'])->group(function () {
    Route::prefix('auth')->group(function () {
        // Guest middleware
        // Xác thực đăng nhập hay đăng ký.
        Route::post('authenticate', [AuthController::class, 'authenticate']);
        // Đăng nhập vào hệ thống.
        Route::post('login', [AuthController::class, 'login']);
        // Quên mật khẩu.
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        // Xác thực OTP quên mật khẩu.
        Route::post('verify-otp-forgot-password', [AuthController::class, 'verifyOtpForgotPassword']);
        // Gửi lại OTP quên mật khẩu.
        Route::post('resend-otp-forgot-password', [AuthController::class, 'resendOtpForgotPassword']);
        // Cập nhật mật khẩu mới.
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
        // Đăng ký tài khoản mới.
        Route::post('register', [AuthController::class, 'register']);
        // Kiểm tra OTP đăng ký và đăng ký tài khoản.
        Route::post('verify-otp-register', [AuthController::class, 'verifyOtpRegister']);
        // Gửi lại OTP đăng ký.
        Route::post('resend-otp-register', [AuthController::class, 'resendOtpRegister']);


        // Auth middleware
        Route::middleware(['auth:sanctum'])->group(function () {
            // Lấy thông tin hồ sơ người dùng.
            Route::get('profile', [AuthController::class, 'getProfile']);
            // Cập nhật ngôn ngữ hệ thống.
            Route::post('set-language', [AuthController::class, 'setLanguage']);
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
         * list address
         */
        Route::get('address', [UserController::class, 'listAddress']);
        /**
         * save address
         */
        Route::post('save', [UserController::class, 'saveAddress']);

        /**
         * set default address
         */
        Route::post('set-default', [UserController::class, 'setDefaultAddress'])->middleware(['throttle:2,1']); // Giới hạn 2 lần trong 1 phút

        /**
         * edit address
         */
        Route::put('edit/{id}', [UserController::class, 'editAddress'])->where('id', '[0-9]+');
        /**
         * delete address
         */
        Route::delete('delete/{id}', [UserController::class, 'deleteAddress'])->where('id', '[0-9]+');
    });

    Route::prefix('profile')->group(function () {
        Route::middleware(['auth:sanctum'])->group(function () {
            // Lấy thông tin hồ sơ người dùng.
            Route::get('dashboard-profile', [ProfileController::class, 'dashboardProfile'])
                ->middleware(['check-role:customer']);
        });

    });

    Route::prefix('user')->group(function () {
        // Lấy danh sách KTV
        Route::get('list-ktv', [UserController::class, 'listKtv']);

        Route::middleware(['auth:sanctum'])->group(function () {
            // Lấy thông tin chi tiết KTV
            Route::get('ktv/{id}', [UserController::class, 'detailKtv'])
                ->where('id', '[0-9]+');
            // Kiểm tra quyền đăng ký đối tác
            Route::get('check-apply-partner', [UserController::class, 'checkApplyPartner'])
                ->middleware(['check-role:customer']);


            // Đăng ký Agency
            Route::post('apply-agency', [UserController::class, 'applyAgency'])
                ->middleware(['throttle:5,1', 'check-role:customer']);

            // Đăng ký KTV
            Route::post('apply-technical', [UserController::class, 'applyTechnical'])
                ->middleware(['throttle:5,1', 'check-role:customer']);

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
            // Lấy danh sách mã giảm giá
            Route::get('list-coupon', [ServiceController::class, 'listCoupon']);
            // Lấy danh sách mã giảm giá của người dùng
            Route::get('my-list-coupon', [ServiceController::class, 'myListCoupon']);
            // Tạo đánh giá cho booking dịch vụ
            Route::post('review', [ServiceController::class, 'createReview']);
            // Lấy danh sách đánh giá của dịch vụ
            Route::get('list-review', [ServiceController::class, 'listReview']);
            // Dịch vụ dịch ngôn ngữ
            Route::post('translate-review', [ServiceController::class, 'translateReview']);
        });
    });

    Route::prefix('booking')->group(function () {
        // router cần auth
        Route::middleware(['auth:sanctum'])->group(function () {
            // Lấy danh sách lịch đặt
            Route::get('list', [BookingController::class, 'listBooking']);

            // Chuẩn bị đặt lịch
            Route::post('prepare-booking', [BookingController::class, 'prepareBooking'])
                ->middleware(['check-role:customer']); // Chỉ cho phép Customer chuẩn bị prepare-booking

            // Đặt lịch dịch vụ
            Route::post('booking-service', [BookingController::class, 'booking'])
                ->middleware(['check-role:customer']); // Chỉ cho phép Customer đặt lịch

            // kiểm tra trạng thái booking
            Route::get('{bookingId}', [BookingController::class, 'checkBooking']);

            // Lấy thông tin chi tiết lịch đặt
            Route::get('detail/{id}', [BookingController::class, 'detailBooking'])
                ->where('id', '[0-9]+');

            // Hủy lịch đặt
            Route::post('cancel', [BookingController::class, 'cancelBooking'])
                ->middleware(['check-role:customer,ktv']);

            Route::middleware(['check-role:ktv'])->group(function () {
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
            // Xử lý webhook ZaloPay
            Route::post('zalopay', [PaymentController::class, 'handleWebhookZaloPay'])->name('webhook.zalopay');
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
            // Lấy thông tin tài khoản rút tiền
            Route::get('info-withdraw', [WithdrawController::class, 'getWithdrawInfo']);
            // Tạo thông tin tài khoản rút tiền
            Route::post('info-withdraw', [WithdrawController::class, 'createWithdrawInfo']);
            // Xóa thông tin tài khoản rút tiền
            Route::delete('info-withdraw/{id}', [WithdrawController::class, 'deleteWithdrawInfo']);
            // Yêu cầu rút tiền
            Route::post('request-withdraw', [WithdrawController::class, 'requestWithdraw']);
        });
    });

    Route::prefix('file')->group(function () {
        Route::get('contract', [FileController::class, 'getContract']);
        Route::get('user-file-private/{id}', [FileController::class, 'getPrivateFile'])
            ->name('file.user-file-private')
            ->where('id', '[0-9]+');
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
        // Đối chiếu Affiliate Link
        Route::get('match', [AffiliateController::class, 'matchAffiliate']);

        Route::middleware(['auth:sanctum'])->group(function () {
            // Lấy thông tin cấu hình affiliate
            Route::get('config', [AffiliateController::class, 'getConfigAffiliate']);
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
        // Translate message
        Route::post('translate', [ChatController::class, 'translateMessage']);

        Route::middleware(['check-role:ktv'])->group(function () {
            // Lấy danh sách phòng chat KTV
            Route::get('ktv-conversations', [ChatController::class, 'listKtvConversation']);
        });
    });

    Route::prefix('config')->group(function () {
        // Lấy thông tin cấu hình hỗ trợ
        Route::get('support-channels', [ConfigController::class, 'getSupportChannels']);

        // Lấy thông tin cấu hình ứng dụng
        Route::get('config-application', [ConfigController::class, 'configApplication']);

    });

    // Chỉ dành cho KTV
    Route::prefix('ktv')->middleware(['auth:sanctum', 'check-role:ktv'])->group(function () {
        // Lấy thông tin dashboard profile của user hiện tại
        Route::get('dashboard', [KTVController::class, 'dashboard']);
        // Lấy danh sách booking của KTV
        Route::get('list-booking', [KTVController::class, 'listBooking']);
        // Lấy tất cả categories
        Route::get('all-categories', [KTVController::class, 'allCategories']);
        // Kết thúc dịch vụ
        Route::post('finish-booking', [BookingController::class, 'finishBooking']);
        // Cập nhật service (toggle)
        Route::post('set-service/{id}', [KTVController::class, 'setService'])->where('id', '[0-9]+');
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
        // Gửi hỗ trợ nguy hiểm
        Route::post('danger-support', [KTVController::class, 'dangerSupport']);
    });

    // Dành cho agency
    Route::prefix('agency')->middleware(['auth:sanctum', 'check-role:agency'])->group(function () {
        // Lấy thông tin dashboard
        Route::get('dashboard', [AgencyController::class, 'dashboard']);
        // Lấy danh sách KTV Performance
        Route::get('list-ktv-performance', [AgencyController::class, 'listKtvPerformance']);
        // Lấy thông tin profile Agency
        Route::get('profile', [AgencyController::class, 'profile']);
        // Cập nhật thông tin profile
        Route::post('edit-profile', [AgencyController::class, 'editProfile']);

    });
});
