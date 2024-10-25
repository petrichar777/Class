<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use OSS\OssClient;

class OssServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(OssClient::class, function ($app) {
            return new OssClient(
                env('OSS_ACCESS_KEY_ID'),
                env('OSS_ACCESS_KEY_SECRET'),
                env('OSS_ENDPOINT')
            );
        });
    }
}
