<?php

namespace App\Providers;

use App\Contracts\SmsSender;
use App\Http\Middleware\EnsureUserHasRole;
use App\Services\Sms\LogSmsSender;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Only the self-hosted log driver exists. Bind another SmsSender
        // implementation here when a provider is added.
        $this->app->singleton(SmsSender::class, function ($app) {
            return new LogSmsSender($app['config']->get('freight.sms.log_channel'));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Re-apply the role middleware on Livewire update requests so panel
        // components keep the same access rules as their initial page load.
        Livewire::addPersistentMiddleware([
            EnsureUserHasRole::class,
        ]);

        // Trust X-Forwarded-* headers from the configured reverse proxies so
        // URLs use https when HTTPS is terminated by nginx or Caddy.
        $proxies = config('freight.trusted_proxies');

        if (filled($proxies)) {
            TrustProxies::at($proxies);
        }
    }
}
