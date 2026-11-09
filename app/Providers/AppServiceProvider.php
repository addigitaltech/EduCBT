<?php

namespace App\Providers;

use App\Domain\Settings\SettingsService;
use App\Support\Profile\Profile;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsService::class);
    }

    public function boot(): void
    {
        // Broad per-IP limiter for the sign-in endpoint. The per-account limiter
        // lives in App\Http\Requests\LoginRequest.
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));

        View::composer(['layouts.app', 'layouts.guest'], function ($view): void {
            try {
                $settings = $this->app->make(SettingsService::class);
                $accent = (string) $settings->get('appearance', 'accent');
                $displayName = $settings->get('appearance', 'display_name');
            } catch (Throwable) {
                $accent = 'navy';
                $displayName = null;
            }

            $view->with([
                'accent' => $accent,
                'orgName' => $displayName ?: config('app.name'),
                'profileLabel' => Profile::current()->label(),
            ]);
        });
    }
}
