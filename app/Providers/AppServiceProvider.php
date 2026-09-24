<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request): Limit {
            return Limit::perMinute(5)->by(
                mb_strtolower((string) $request->input('email')).'|'.$request->ip()
            );
        });

        RateLimiter::for('chat', function (Request $request): Limit {
            $key = $request->user()?->id ?: $request->ip();

            return Limit::perMinute(30)->by('chat|'.$key);
        });

        RateLimiter::for('import', function (Request $request): Limit {
            $key = $request->user()?->id ?: $request->ip();

            return Limit::perMinute(12)->by('import|'.$key);
        });
    }
}

