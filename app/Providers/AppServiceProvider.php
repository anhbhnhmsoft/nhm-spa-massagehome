<?php

namespace App\Providers;

use App\Filament\Pages\Dashboard;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Page;
use App\Models\Service;
use App\Models\StaticContract;
use App\Models\UserFile;
use App\Models\UserProfile;
use App\Observers\BannerObserver;
use App\Observers\CategoryObserver;
use App\Observers\CouponObserver;
use App\Observers\PageObserver;
use App\Observers\ServiceObserver;
use App\Observers\StaticContractObserver;
use App\Observers\UserFileObserver;
use App\Observers\UserProfileObserver;
use App\Repositories\AffiliateConfigRepository;
use App\Repositories\BookingRepository;
use App\Repositories\CategoryPriceRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\ConfigRepository;
use App\Repositories\CouponRepository;
use App\Repositories\CouponUsedRepository;
use App\Repositories\CouponUserRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\ProvinceRepository;
use App\Repositories\ReviewRepository;
use App\Repositories\ServiceRepository;
use App\Repositories\StaticContractRepository;
use App\Repositories\UserFileRepository;
use App\Repositories\UserKtvScheduleRepository;
use App\Repositories\UserProfileRepository;
use App\Repositories\UserRepository;
use App\Repositories\UserReviewApplicationRepository;
use App\Repositories\UserWithdrawInfoRepository;
use App\Repositories\WalletRepository;
use App\Repositories\WalletTransactionRepository;
use App\Repositories\ChatRoomRepository;
use App\Repositories\MessageRepository;
use App\Services\AgencyService;
use App\Services\AuthService;
use App\Services\BookingService;
use App\Services\ChatService;
use App\Services\ConfigService;
use App\Services\CouponService;
use App\Services\NotificationService;
use App\Services\PaymentService;
use App\Services\PayOsService;
use App\Services\ProvinceService;
use App\Services\ServiceService;
use App\Services\UserService;
use App\Services\UserWithdrawInfoService;
use App\Services\WalletService;
use App\Services\AffiliateService;
use App\Services\ReviewService;
use App\Services\UserFileService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register repositories
        $this->registerRepository();

        // Register services
        $this->registerService();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register observers
        $this->registerObservers();
    }

    protected function registerRepository(): void
    {
        $this->app->singleton(ConfigRepository::class);
        $this->app->singleton(UserRepository::class);
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
    }

    /**
     * Register services.
     * @return void
     */
    protected function registerService(): void
    {
        // Register AuthService
        $this->app->singleton(ConfigService::class);
        $this->app->singleton(PayOsService::class);
        $this->app->singleton(AuthService::class);
        $this->app->singleton(ServiceService::class);
        $this->app->singleton(UserService::class);
        $this->app->singleton(BookingService::class);
        $this->app->singleton(PaymentService::class);
        $this->app->singleton(NotificationService::class);
        $this->app->singleton(ChatService::class);
        $this->app->singleton(Dashboard::class);
        $this->app->singleton(PayOsService::class);
        $this->app->singleton(WalletService::class);
        $this->app->singleton(CouponService::class);
        $this->app->singleton(AffiliateService::class);
        $this->app->singleton(ProvinceService::class);
        $this->app->singleton(ReviewService::class);
        $this->app->singleton(UserWithdrawInfoService::class);
        $this->app->singleton(UserFileService::class);
        $this->app->singleton(AgencyService::class);
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
    }
}
