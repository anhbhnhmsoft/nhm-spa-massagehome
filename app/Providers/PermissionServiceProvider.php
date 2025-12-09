<?php

namespace App\Providers;

use App\Policies\UserFilePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class PermissionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerGates();
    }

    protected function registerGates(): void
    {
        Gate::define('download-user-file', [UserFilePolicy::class, 'download']);
    }
}
