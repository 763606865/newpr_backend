<?php

namespace App\Libs\Oss;

use App\Libs\Oss\Adapter\OssAdapter;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use League\Flysystem\Filesystem;
use OSS\OssClient;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Storage::extend('oss', static function ($app, $config) {
            $client = new OssClient($config['access_key_id'], $config['access_key_secret'], $config['endpoint'], false);
            $adapter = new OssAdapter($client, $config['bucket']);
            $driver = new Filesystem($adapter);
            return new FilesystemAdapter($driver, $adapter, $config);
        });
    }
}
