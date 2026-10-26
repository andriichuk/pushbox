<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Tests\Feature;

use Andriichuk\Pushbox\PushboxServiceProvider;
use Andriichuk\Pushbox\Sending\SendFcmNotification;
use Andriichuk\Pushbox\Tests\TestCase;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;

class EnablementTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function environments(): array
    {
        return [
            'local' => ['local'],
            'testing' => ['testing'],
            'staging' => ['staging'],
            'production' => ['production'],
            'custom' => ['preview'],
        ];
    }

    #[DataProvider('environments')]
    public function test_routes_are_enabled_by_default_in_every_environment(string $environment): void
    {
        self::assertTrue(config('pushbox.enabled'));
        $this->app['env'] = $environment;
        $this->app['router']->setRoutes(new RouteCollection);
        (new PushboxServiceProvider($this->app))->boot();
        $this->withSession(['_token' => 'test-csrf'])->withHeader('X-CSRF-TOKEN', 'test-csrf');

        $this->get('/pushbox')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->post('/pushbox/device-token', ['fcm_token' => 'test-token'])->assertRedirect();
        $this->post('/pushbox/device-token/clear')->assertRedirect();
    }

    #[DataProvider('environments')]
    public function test_disabled_routes_are_not_registered_in_any_environment(string $environment): void
    {
        $this->app['env'] = $environment;
        config(['pushbox.enabled' => false]);
        $this->app['router']->setRoutes(new RouteCollection);
        (new PushboxServiceProvider($this->app))->boot();

        $this->get('/pushbox')->assertNotFound();
        $this->post('/pushbox/device-token')->assertNotFound();
        $this->post('/pushbox/device-token/clear')->assertNotFound();
        $this->post('/pushbox/send')->assertNotFound();
    }

    #[DataProvider('environments')]
    public function test_disabling_registered_routes_blocks_every_action(string $environment): void
    {
        $this->app['env'] = $environment;
        config(['pushbox.enabled' => false, 'pushbox.send.enabled' => true]);
        $this->mock(SendFcmNotification::class)->shouldNotReceive('send');
        $this->withSession(['_token' => 'test-csrf'])->withHeader('X-CSRF-TOKEN', 'test-csrf');

        $this->get('/pushbox')->assertNotFound();
        $this->post('/pushbox/device-token')->assertNotFound();
        $this->post('/pushbox/device-token/clear')->assertNotFound();
        $this->post('/pushbox/send')->assertNotFound();
    }

    #[DataProvider('environments')]
    public function test_a_defined_gate_is_enforced_in_every_environment(string $environment): void
    {
        $this->app['env'] = $environment;
        config(['pushbox.send.enabled' => true]);
        $this->mock(SendFcmNotification::class)->shouldNotReceive('send');
        $this->withSession(['_token' => 'test-csrf'])->withHeader('X-CSRF-TOKEN', 'test-csrf');
        Gate::define('viewPushbox', fn ($user = null): bool => false);

        $this->get('/pushbox')->assertForbidden();
        $this->post('/pushbox/device-token')->assertForbidden();
        $this->post('/pushbox/device-token/clear')->assertForbidden();
        $this->post('/pushbox/send')->assertForbidden();

        Gate::define('viewPushbox', fn ($user = null): bool => true);
        $this->get('/pushbox')->assertOk();
        $this->post('/pushbox/device-token', ['fcm_token' => 'test-token'])->assertRedirect();
        $this->post('/pushbox/device-token/clear')->assertRedirect();
    }
}
