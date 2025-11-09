<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\CouchDB\CouchClient;

class CouchDBServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CouchClient::class, fn () => new CouchClient());
    }

    public function boot(): void {}
}
