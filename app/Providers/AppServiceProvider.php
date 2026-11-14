<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Tenancy\TenantManager;
use App\Services\Tenancy\TenantUserProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantManager::class, fn (): TenantManager => new TenantManager);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::provider('tenant_eloquent', function (Application $app, array $config): TenantUserProvider {
            return new TenantUserProvider($app['hash'], $config['model']);
        });

        Queue::after(function (): void {
            if ($this->app->resolved(TenantManager::class)) {
                $this->app->make(TenantManager::class)->reset();
            }
        });

        Queue::failing(function (): void {
            if ($this->app->resolved(TenantManager::class)) {
                $this->app->make(TenantManager::class)->reset();
            }
        });
    }
}
