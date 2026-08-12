<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }
    public function boot(): void
    {
        RateLimiter::for('log',function(Request $request)
        {
            return Limit::perMinute(5)->by($request->input('email'))->response(function(Request $request,array $headers)
            {
                return response('Too many login attempts, Please try again later')->withHeaders($headers);
            });
        });
    }
}
