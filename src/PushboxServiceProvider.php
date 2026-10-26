<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox;

use Andriichuk\Pushbox\Http\Middleware\EnsurePushboxEnabled;
use Andriichuk\Pushbox\Listeners\RecordFcmSendResult;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class PushboxServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/pushbox.php', 'pushbox');

        $this->app->scoped(Pushbox::class);

        $this->app->alias(Pushbox::class, 'pushbox');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'pushbox');
        View::composer('pushbox::index', function ($view): void {
            $view->with('pushboxFavicon', 'data:image/svg+xml,'.rawurlencode(file_get_contents(__DIR__.'/../resources/favicon.svg')));
            $view->with('pushboxCss', file_get_contents(__DIR__.'/../resources/dist/pushbox.css'));
            $view->with('pushboxJs', file_get_contents(__DIR__.'/../resources/js/pushbox.js'));
        });

        Event::listen([NotificationSent::class, NotificationFailed::class], RecordFcmSendResult::class);

        RateLimiter::for('pushbox-send', fn (Request $request) => Limit::perMinute(10)->by($request->ip())
        );

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
        if ($this->app->routesAreCached() || ! config('pushbox.enabled', true)) {
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
}
