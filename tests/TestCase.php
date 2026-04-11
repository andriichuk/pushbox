<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Tests;

use Andriichuk\Pushbox\PushboxServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            PushboxServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        $app['config']->set('pushbox.enabled', true);
        $app['config']->set('pushbox.local_only', false);
        $app['config']->set('pushbox.send.enabled', false);
    }
}
