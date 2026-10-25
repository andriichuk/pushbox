<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Tests\Feature;

use Andriichuk\Pushbox\Http\Controllers\PushboxController;
use Andriichuk\Pushbox\Pushbox;
use Andriichuk\Pushbox\Sending\SendFcmNotification;
use Andriichuk\Pushbox\Tests\Fixtures\ExampleNotification;
use Andriichuk\Pushbox\Tests\TestCase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Mockery;
use RuntimeException;

class LaunchSafetyTest extends TestCase
{
    public function test_empty_install_and_malformed_query_render_safely(): void
    {
        $this->get('/pushbox')->assertOk()->assertSee('Register your first notification');
        $this->get('/pushbox?class[]=invalid&locale[]=invalid&variant[]=invalid')->assertOk();
    }

    public function test_preview_exception_is_displayed_and_other_entries_remain_visible(): void
    {
        app(Pushbox::class)->add(function (): ExampleNotification {
            throw new RuntimeException('<broken factory>');
        });
        $this->get('/pushbox')->assertOk()->assertSee('Unable to preview this notification')
            ->assertSee('&lt;broken factory&gt;', false)->assertSee('ExampleNotification');
    }

    public function test_save_and_clear_never_send_even_when_sending_is_enabled(): void
    {
        config(['pushbox.send.enabled' => true, 'pushbox.send_allow_non_local' => true]);
        $this->mock(SendFcmNotification::class)->shouldNotReceive('send');
        $this->post('/pushbox/device-token', ['fcm_token' => 'saved-token'])->assertRedirect();
        self::assertSame('saved-token', session(PushboxController::SESSION_FCM_DEVICE_TOKEN));
        $this->post('/pushbox/device-token/clear')->assertRedirect();
        self::assertNull(session(PushboxController::SESSION_FCM_DEVICE_TOKEN));
        $this->withSession([PushboxController::SESSION_FCM_DEVICE_TOKEN => 'old-token'])
            ->post('/pushbox/device-token', ['fcm_token' => ''])->assertRedirect();
        self::assertNull(session(PushboxController::SESSION_FCM_DEVICE_TOKEN));
    }

    public function test_send_requires_a_current_target(): void
    {
        $this->enableSending();
        $this->mock(SendFcmNotification::class)->shouldNotReceive('send');
        $this->post('/pushbox/send', ['class' => ExampleNotification::class])->assertSessionHasErrors(['target_hash']);
        $this->post('/pushbox/send', $this->sendData('old-token'))->assertSessionHasErrors(['pushbox']);
    }

    public function test_skipped_result_is_not_logged_as_accepted(): void
    {
        $this->enableSending();
        $this->mock(SendFcmNotification::class)->shouldReceive('send')->once()->andReturn([
            'ok' => false,
            'status' => 'skipped',
            'note' => 'The notification was skipped.',
        ]);
        Log::spy();
        $this->post('/pushbox/send', $this->sendData('test-token'))
            ->assertSessionHas('pushbox_send_result.ok', false)
            ->assertSessionHas('pushbox_send_result.status', 'skipped')
            ->assertSessionMissing('status');
        Log::shouldHaveReceived('log')->once()->with('warning', 'pushbox.skipped', Mockery::type('array'));
    }

    public function test_send_disabled_and_nonlocal_disabled_are_enforced(): void
    {
        $this->mock(SendFcmNotification::class)->shouldNotReceive('send');
        $this->post('/pushbox/send')->assertForbidden();
        config(['pushbox.send.enabled' => true]);
        $this->post('/pushbox/send')->assertForbidden();
    }

    public function test_request_time_environment_guard_denies_previously_registered_routes(): void
    {
        config(['pushbox.local_only' => true]);
        $this->app['env'] = 'production';
        $this->withSession(['_token' => 'test-csrf'])->withHeader('X-CSRF-TOKEN', 'test-csrf');
        $this->get('/pushbox')->assertNotFound();
        $this->post('/pushbox/device-token')->assertNotFound();
        $this->post('/pushbox/send')->assertNotFound();
    }

    public function test_staging_requires_explicit_gate_even_with_nonlocal_access_enabled(): void
    {
        $this->app['env'] = 'staging';
        $this->withSession(['_token' => 'test-csrf'])->withHeader('X-CSRF-TOKEN', 'test-csrf');
        config(['pushbox.local_only' => false]);
        $this->get('/pushbox')->assertForbidden();
        $this->post('/pushbox/device-token')->assertForbidden();
        $this->post('/pushbox/device-token/clear')->assertForbidden();
        $this->post('/pushbox/send')->assertForbidden();
        Gate::define('viewPushbox', fn ($user = null) => true);
        $this->get('/pushbox')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_send_is_rate_limited(): void
    {
        $this->enableSending();
        $this->mock(SendFcmNotification::class)->shouldReceive('send')->times(10)->andReturn([
            'ok' => false,
            'status' => 'skipped',
        ]);
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->post('/pushbox/send', $this->sendData('test-token'))->assertRedirect();
        }
        $this->post('/pushbox/send', $this->sendData('test-token'))->assertStatus(429);
    }

    public function test_page_contains_bundled_assets_without_remote_scripts_or_fonts(): void
    {
        $html = $this->get('/pushbox')->assertOk()->getContent();
        self::assertStringNotContainsString('cdn.jsdelivr.net', $html);
        self::assertStringNotContainsString('fonts.googleapis.com', $html);
        self::assertStringContainsString('tailwindcss', $html);
        self::assertStringNotContainsString('data-confirm-send', $html);
        self::assertStringNotContainsString('window.confirm', $html);
    }

    private function enableSending(): void
    {
        app(Pushbox::class)->add(ExampleNotification::class);
        config(['pushbox.send.enabled' => true, 'pushbox.send_allow_non_local' => true, 'pushbox.send.fcm.token' => 'test-token']);
    }

    private function sendData(string $token): array
    {
        return ['class' => ExampleNotification::class, 'target_hash' => hash('sha256', $token)];
    }
}
