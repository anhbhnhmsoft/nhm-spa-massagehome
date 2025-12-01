<?php

namespace App\Providers;

use App\Repositories\UserProfileRepository;
use App\Repositories\UserRepository;
use App\Repositories\WalletRepository;
use App\Services\AuthService;
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
        $this->app->singleton(UserRepository::class);
        $this->app->singleton(UserProfileRepository::class);
        $this->app->singleton(WalletRepository::class);
    }

    /**
     * Register services.
     * @return void
     */
    protected function registerService(): void
    {
        // Register AuthService
        $this->app->singleton(AuthService::class);
    }
}
