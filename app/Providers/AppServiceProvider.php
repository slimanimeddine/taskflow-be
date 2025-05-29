<?php

namespace App\Providers;

use App\Traits\ApiResponses;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    use ApiResponses;

    /**
     * Register any application services.
     */
    public function register(): void {}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('sign-in', fn (Request $request) => [
            Limit::perMinute(5)
                ->by(Str::lower($request->input('email')).'|'.$request->ip())
                ->response(fn (Request $request, array $headers) => $this->rateLimitExceeded(
                    'Too many login attempts.',
                )),
        ]);
    }
}
