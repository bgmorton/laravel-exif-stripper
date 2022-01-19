<?php

namespace bgmorton\LaravelExifStripper;

use Illuminate\Contracts\Http\Kernel;
use bgmorton\LaravelExifStripper\Http\Middleware\LaravelExifStripperMiddleware;
use Illuminate\Support\ServiceProvider;

class LaravelExifStripperServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/laravel-exif-stripper.php', 'laravel-exif-stripper');
    }


    public function boot(Kernel $kernel)
    {
        if ($this->app->runningInConsole()) {

            $this->publishes([
                __DIR__ . '/../config/laravel-exif-stripper.php' => config_path('laravel-exif-stripper.php'),
            ], 'config');
        }

        $kernel->pushMiddleware(LaravelExifStripperMiddleware::class);
    }
}
