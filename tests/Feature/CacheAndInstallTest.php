<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Tests\Feature;

use Andriichuk\Pushbox\Pushbox;
use Andriichuk\Pushbox\PushboxServiceProvider;
use Andriichuk\Pushbox\Tests\Fixtures\ExampleNotification;
use Andriichuk\Pushbox\Tests\TestCase;
use Illuminate\Routing\RouteCollection;

class CacheAndInstallTest extends TestCase
{
    public function test_cached_routes_remain_protected_after_environment_changes(): void
    {
        $routes = $this->app['router']->getRoutes();

        foreach ($routes as $route) {
            $route->prepareForSerialization();
        }
        $compiled = $routes->compile();
        $this->app['router']->setCompiledRoutes($compiled);
        $this->app->instance('routes.cached', true);
        config(['pushbox.local_only' => true]);
        $this->app['env'] = 'production';
        $this->get('/pushbox')->assertNotFound();
    }

    public function test_local_request_without_csrf_cannot_change_token(): void
    {
        $this->app['env'] = 'local';
        $this->post('/pushbox/device-token', ['fcm_token' => 'test-token'])->assertStatus(419);
        $this->withSession(['_token' => 'valid-csrf'])
            ->post('/pushbox/device-token', ['fcm_token' => 'test-token', '_token' => 'valid-csrf'])->assertRedirect();
    }

    public function test_custom_path_is_used_by_forms_and_links(): void
    {
        $this->app['router']->setRoutes(new RouteCollection);
        config(['pushbox.path' => 'developer/pushes']);
        (new PushboxServiceProvider($this->app))->boot();
        $this->get('/developer/pushes')->assertOk()->assertSee('/developer/pushes/device-token', false);
        $this->get('/pushbox')->assertNotFound();
    }

    public function test_scoped_registry_does_not_retain_previous_request_objects(): void
    {
        $first = app(Pushbox::class);
        $first->add(ExampleNotification::class);
        $this->app->forgetScopedInstances();
        self::assertNotSame($first, app(Pushbox::class));
        self::assertCount(0, app(Pushbox::class)->notifications());
    }
}
