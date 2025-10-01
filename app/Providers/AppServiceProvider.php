<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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
        // Ép mọi URL (asset, css/js của Swagger, route…) dùng HTTPS ở môi trường production
        if (app()->environment('production')) {
            URL::forceScheme('https');

            // Nếu bạn có đặt APP_URL, đảm bảo Laravel dùng làm root URL
            if ($appUrl = config('app.url')) {
                URL::forceRootUrl($appUrl);
            }
        }
    }
}
