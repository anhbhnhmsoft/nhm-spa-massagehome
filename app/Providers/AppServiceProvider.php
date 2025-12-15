<?php

namespace App\Providers;

use App\Filament\Pages\Dashboard;
use App\Repositories\AffiliateConfigRepository;
use App\Repositories\AffiliateEarningRepository;
use App\Repositories\BookingRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\ConfigRepository;
use App\Repositories\CouponRepository;
use App\Repositories\CouponUsedRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\ReviewRepository;
use App\Repositories\ServiceRepository;
use App\Repositories\UserFileRepository;
use App\Repositories\UserProfileRepository;
use App\Repositories\UserRepository;
use App\Repositories\UserReviewApplicationRepository;
use App\Repositories\WalletRepository;
use App\Repositories\WalletTransactionRepository;
use App\Services\AuthService;
use App\Services\BookingService;
use App\Services\ConfigService;
use App\Services\CouponService;
use App\Services\NotificationService;
use App\Services\PaymentService;
use App\Services\PayOsService;
use App\Services\ServiceService;
use App\Services\UserService;
use App\Services\WalletService;
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
        //
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
        $this->app->singleton(AffiliateEarningRepository::class);
        $this->app->singleton(ReviewRepository::class);
        $this->app->singleton(CouponUsedRepository::class);
        $this->app->singleton(NotificationRepository::class);

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
        $this->app->singleton(Dashboard::class);
        $this->app->singleton(PayOsService::class);
        $this->app->singleton(WalletService::class);
        $this->app->singleton(CouponService::class);
    }
}
