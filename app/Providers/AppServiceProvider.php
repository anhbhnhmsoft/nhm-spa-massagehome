<?php

namespace App\Providers;

use App\Enums\Admin\AdminGate;
use App\Enums\Admin\AdminRole;
use App\Filament\Pages\Dashboard;
use App\Models\AdminUser;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Config;
use App\Models\Coupon;
use App\Models\Page;
use App\Models\Service;
use App\Models\StaticContract;
use App\Models\UserFile;
use App\Models\UserProfile;
use App\Observers\BannerObserver;
use App\Observers\CategoryObserver;
use App\Observers\ConfigObserver;
use App\Observers\CouponObserver;
use App\Observers\PageObserver;
use App\Observers\ServiceObserver;
use App\Observers\StaticContractObserver;
use App\Observers\UserFileObserver;
use App\Observers\UserProfileObserver;
use App\Repositories\AdminUserRepository;
use App\Repositories\AffiliateConfigRepository;
use App\Repositories\BookingRepository;
use App\Repositories\CategoryPriceRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\ChatRoomRepository;
use App\Repositories\ConfigRepository;
use App\Repositories\CouponRepository;
use App\Repositories\CouponUsedRepository;
use App\Repositories\CouponUserRepository;
use App\Repositories\DangerSupportRepository;
use App\Repositories\MessageRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\ProvinceRepository;
use App\Repositories\ReviewRepository;
use App\Repositories\ServiceRepository;
use App\Repositories\StaticContractRepository;
use App\Repositories\UserDeviceRepository;
use App\Repositories\UserFileRepository;
use App\Repositories\UserKtvScheduleRepository;
use App\Repositories\UserOtpRepository;
use App\Repositories\UserProfileRepository;
use App\Repositories\UserRepository;
use App\Repositories\UserReviewApplicationRepository;
use App\Repositories\UserWithdrawInfoRepository;
use App\Repositories\WalletRepository;
use App\Repositories\WalletTransactionRepository;
use App\Repositories\ZaloTokenRepository;
use App\Services\AffiliateService;
use App\Services\AgencyService;
use App\Services\AuthService;
use App\Services\BookingService;
use App\Services\ChatService;
use App\Services\ConfigService;
use App\Services\CouponService;
use App\Services\Facades\BookingFacadeService;
use App\Services\Facades\TransactionJobService;
use App\Services\GeminiService;
use App\Services\NotificationService;
use App\Services\PaymentService;
use App\Services\PayOsService;
use App\Services\ProfileService;
use App\Services\ProvinceService;
use App\Services\ReviewService;
use App\Services\ServiceService;
use App\Services\UserFileService;
use App\Services\UserService;
use App\Services\UserWithdrawInfoService;
use App\Services\Validator\BookingValidator;
use App\Services\Validator\CouponValidator;
use App\Services\WalletService;
use App\Services\ZaloService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Opcodes\LogViewer\Facades\LogViewer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register repositories
        $this->registerRepository();

        // Register Validator
        $this->registerValidator();

        // Register services
        $this->registerService();

        // Register facades services
        $this->registerFacadesService();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register observers
        $this->registerObservers();

        // Register log viewer
        $this->registerLogViewer();

        // Register admin gates
        $this->registerAdminGate();

    }

    /**
     * Register repositories.
     * @return void
     */
    protected function registerRepository(): void
    {
        $this->app->singleton(ConfigRepository::class);
        $this->app->singleton(AdminUserRepository::class);
        $this->app->singleton(UserRepository::class);
        $this->app->singleton(UserDeviceRepository::class);
        $this->app->singleton(UserProfileRepository::class);
        $this->app->singleton(WalletRepository::class);
        $this->app->singleton(ServiceRepository::class);
        $this->app->singleton(CategoryRepository::class);
        $this->app->singleton(UserFileRepository::class);
        $this->app->singleton(UserReviewApplicationRepository::class);
        $this->app->singleton(BookingRepository::class);
        $this->app->singleton(CouponRepository::class);
        $this->app->singleton(UserFileRepository::class);
        $this->app->singleton(UserReviewApplicationRepository::class);
        $this->app->singleton(WalletTransactionRepository::class);
        $this->app->singleton(AffiliateConfigRepository::class);
        $this->app->singleton(ReviewRepository::class);
        $this->app->singleton(CouponUsedRepository::class);
        $this->app->singleton(NotificationRepository::class);
        $this->app->singleton(ProvinceRepository::class);
        $this->app->singleton(CouponUserRepository::class);
        $this->app->singleton(ChatRoomRepository::class);
        $this->app->singleton(MessageRepository::class);
        $this->app->singleton(UserWithdrawInfoRepository::class);
        $this->app->singleton(StaticContractRepository::class);
        $this->app->singleton(UserKtvScheduleRepository::class);
        $this->app->singleton(CategoryPriceRepository::class);
        $this->app->singleton(ZaloTokenRepository::class);
        $this->app->singleton(DangerSupportRepository::class);
        $this->app->singleton(UserOtpRepository::class);
    }

    /**
     * Register validators.
     * @return void
     */
    protected function registerValidator(): void
    {
        $this->app->singleton(BookingValidator::class);
        $this->app->singleton(CouponValidator::class);
    }


    /**
     * Register services.
     * @return void
     */
    protected function registerService(): void
    {
        // Register AuthService
        $this->app->singleton(ConfigService::class);
        $this->app->singleton(GeminiService::class);
        $this->app->singleton(PayOsService::class);
        $this->app->singleton(AuthService::class);
        $this->app->singleton(ServiceService::class);
        $this->app->singleton(UserService::class);
        $this->app->singleton(BookingService::class);
        $this->app->singleton(WalletService::class);
        $this->app->singleton(PaymentService::class);
        $this->app->singleton(NotificationService::class);
        $this->app->singleton(ChatService::class);
        $this->app->singleton(Dashboard::class);
        $this->app->singleton(PayOsService::class);
        $this->app->singleton(CouponService::class);
        $this->app->singleton(AffiliateService::class);
        $this->app->singleton(ProvinceService::class);
        $this->app->singleton(ReviewService::class);
        $this->app->singleton(UserWithdrawInfoService::class);
        $this->app->singleton(UserFileService::class);
        $this->app->singleton(AgencyService::class);
        $this->app->singleton(ZaloService::class);
        $this->app->singleton(ProfileService::class);
    }


    /**
     * Đăng ký các service tổng (Facade).
     * @return void
     */
    protected function registerFacadesService(): void
    {
        $this->app->singleton(TransactionJobService::class);
        $this->app->singleton(BookingFacadeService::class);
    }

    /**
     * Register observers.
     */
    protected function registerObservers(): void
    {
        Banner::observe(BannerObserver::class);
        Category::observe(CategoryObserver::class);
        Service::observe(ServiceObserver::class);
        Coupon::observe(CouponObserver::class);
        Page::observe(PageObserver::class);
        UserFile::observe(UserFileObserver::class);
        UserProfile::observe(UserProfileObserver::class);
        StaticContract::observe(StaticContractObserver::class);
        Config::observe(ConfigObserver::class);
    }

    /**
     * Register log viewer.
     * @return void
     */
    protected function registerLogViewer(): void
    {
        LogViewer::auth(function () {
            $user = Auth::guard('web')->user();
            if (!$user) {
                return false;
            }
            if (!$user->hasRole(AdminRole::ADMIN)) {
                return false;
            }
            return true;
        });
    }

    /**
     * Đăng ký các gate cho admin.
     * @return void
     */
    protected function registerAdminGate(): void
    {
        Gate::define(AdminGate::ALLOW_ADMIN, function (AdminUser $user) {
            return $user->hasRole(AdminRole::ADMIN);
        });

        Gate::define(AdminGate::ALLOW_ACCOUNTANT, function (AdminUser $user) {
            return $user->hasAnyRole([AdminRole::ADMIN, AdminRole::ACCOUNTANT]);
        });

        Gate::define(AdminGate::ALLOW_ACCOUNTANT_SELF, function (AdminUser $user) {
            return $user->hasRole(AdminRole::ACCOUNTANT);
        });

        Gate::define(AdminGate::ALLOW_EMPLOYEE, function (AdminUser $user) {
            return $user->hasAnyRole([AdminRole::ADMIN, AdminRole::EMPLOYEE]);
        });

        Gate::define(AdminGate::ALLOW_EMPLOYEE_SELF, function (AdminUser $user) {
            return $user->hasRole(AdminRole::EMPLOYEE);
        });

        Gate::define(AdminGate::ALLOW_FULL, function (AdminUser $user) {
            return $user->hasAnyRole([AdminRole::ADMIN, AdminRole::ACCOUNTANT, AdminRole::EMPLOYEE]);
        });
    }
}
