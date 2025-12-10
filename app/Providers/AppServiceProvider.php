<?php

namespace App\Providers;

use App\Traits\ApiResponses;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
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
        Model::automaticallyEagerLoadRelationships();

        Model::shouldBeStrict();

        Model::unguard();

        Date::use(CarbonImmutable::class);

        if (App::environment('production')) {
            URL::forceHttps();
        }

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );
        // rate limiting
        RateLimiter::for('sign-in', fn (Request $request) => [
            Limit::perMinute(5)
                ->by(Str::lower($request->input('email')).'|'.$request->ip())
                ->response(fn (Request $request, array $headers) => $this->rateLimitExceeded(
                    'Too many login attempts.',
                )),
        ]);
    }
}
