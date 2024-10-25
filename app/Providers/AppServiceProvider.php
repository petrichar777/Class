<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Filesystem;
use League\Flysystem\Aliyun\OssAdapter;
use Oss\OssClient;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Storage::extend('aliyun-oss', function ($app, $config) {
            $client = new OssClient($config['key'], $config['secret'], $config['endpoint']);
            return new Filesystem(new OssAdapter($client, $config['bucket'], $config['prefix']));
        });
    }
}
