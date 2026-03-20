<?php

namespace App\Providers;

use App\Proton\AssetManager;
use App\Proton\Config;
use App\Proton\Data;
use App\Proton\FilesystemManager;
use App\Proton\PageManager;
use App\Proton\Sitemap;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
    }

    /**
     * Register any application services.
     */
    #[\Override]
    public function register(): void
    {
        $this->app->singleton(Config::class, fn (): Config => new Config());

        $this->app->singleton(Data::class, fn ($app): Data => new Data($app->make(Config::class)));

        $this->app->singleton(FilesystemManager::class, fn ($app): FilesystemManager => new FilesystemManager($app->make(Config::class)));

        $this->app->singleton(AssetManager::class, fn ($app): AssetManager => new AssetManager(
            $app->make(Config::class),
            $app->make(FilesystemManager::class),
        ));

        $this->app->singleton(PageManager::class, fn ($app): PageManager => new PageManager(
            $app->make(Config::class),
            $app->make(Data::class),
            $app->make(FilesystemManager::class),
        ));

        $this->app->singleton(Sitemap::class, fn ($app): Sitemap => new Sitemap(
            $app->make(Config::class),
            $app->make(FilesystemManager::class),
        ));
    }
}
