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
        // Bind UserService with its dependencies
        $this->app->bind(\App\Services\UserService::class, function ($app) {
            return new \App\Services\UserService(
                $app->make(\App\Repositories\Interfaces\UsersRepositoryInterface::class),
                $app->make(\App\Repositories\Interfaces\PatientsRepositoryInterface::class),
                $app->make(\App\Repositories\Interfaces\DoctorsRepositoryInterface::class),
                $app->make(\App\Repositories\Interfaces\StaffsRepositoryInterface::class)
            );
        });
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
