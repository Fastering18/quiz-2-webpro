<?php

namespace App\Providers;

use App\Services\DirectSmtpTransport;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Mail::extend('direct', function (array $config = []) {
            return new DirectSmtpTransport(
                (string) ($config['local_domain'] ?? parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost'),
                (int) ($config['timeout'] ?? 20),
            );
        });

        // Sessions are needed by the browser UI (login -> /portal, CSRF). The `array` driver loses them
        // between requests, so outside tests fall back to the `sessions` table (created by the first migration).
        if (config('session.driver') === 'array' && ! $this->app->runningUnitTests()) {
            config(['session.driver' => 'database']);
        }

        if ($this->app->environment('production')) {
            if (config('session.driver') === 'file') {
                config(['session.driver' => 'database']);
            }
            if (config('cache.default') === 'file') {
                config(['cache.default' => 'database']);
            }
            if (config('session.secure') === null) {
                config(['session.secure' => true]);
            }

            // Resend API first. If it throws, Laravel tries direct delivery to the recipient server.
            config(['mail.default' => 'failover']);
        }

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by($request->user()?->getAuthIdentifier() ?: $request->ip()));

        // Brute-force protection for logins: 10/min per username+IP and 30/min per IP.
        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(10)->by('login:'.strtolower((string) $request->input('username')).'|'.$request->ip()),
            Limit::perMinute(30)->by('login-ip:'.$request->ip()),
        ]);
    }
}
