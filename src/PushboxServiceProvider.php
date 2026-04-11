<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox;

use Andriichuk\Pushbox\Http\Middleware\EnsurePushboxEnabled;
use Andriichuk\Pushbox\Preview\FcmPreviewNormalizer;
use Andriichuk\Pushbox\Preview\PreviewResolver;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class PushboxServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/pushbox.php', 'pushbox');

        $this->app->singleton(Pushbox::class, function ($app) {
            return new Pushbox;
        });

        $this->app->alias(Pushbox::class, 'pushbox');

        $this->app->singleton(FcmPreviewNormalizer::class);
        $this->app->singleton(PreviewResolver::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'pushbox');

        $this->registerRoutes();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/pushbox.php' => config_path('pushbox.php'),
            ], 'pushbox-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/pushbox'),
            ], 'pushbox-views');

            $this->commands([
                Console\InstallCommand::class,
            ]);
        }
    }

    private function registerRoutes(): void
    {
        if (! $this->shouldRegisterRoutes()) {
            return;
        }

        $middleware = array_merge(
            (array) config('pushbox.middleware', ['web']),
            [EnsurePushboxEnabled::class]
        );

        Route::middleware($middleware)
            ->prefix((string) config('pushbox.path', 'pushbox'))
            ->group(__DIR__.'/../routes/web.php');
    }

    private function shouldRegisterRoutes(): bool
    {
        if (! config('pushbox.enabled', true)) {
            return false;
        }

        if (config('pushbox.local_only', true) && ! app()->environment(['local', 'testing'])) {
            return false;
        }

        return true;
    }
}
