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

    protected function tearDown(): void
    {
        $path = config('pushbox.route_file');

        if (is_string($path) && is_file($path)) {
            unlink($path);
        }

        parent::tearDown();
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        $app['config']->set('pushbox.route_file', sys_get_temp_dir().'/pushbox-test-'.bin2hex(random_bytes(8)).'.php');
        $app['config']->set('cache.default', 'array');
        $app['config']->set('pushbox.send.enabled', false);
    }
}
