<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Tests\Feature;

use Andriichuk\Pushbox\Pushbox;
use Andriichuk\Pushbox\Sending\SendFcmNotification;
use Andriichuk\Pushbox\Tests\Fixtures\ExampleNotification;
use Andriichuk\Pushbox\Tests\TestCase;

class OptionalDriverTest extends TestCase
{
    public function test_preview_and_send_diagnostic_without_optional_driver(): void
    {
        if (class_exists(SendFcmNotification::FCM_CHANNEL)) {
            self::markTestSkipped('Executed in the CI lane without the optional FCM package.');
        }
        app(Pushbox::class)->add(ExampleNotification::class);
        config(['pushbox.send.enabled' => true, 'pushbox.send.fcm.token' => 'test-token']);
        $this->get('/pushbox')->assertOk()->assertSee('Hello')->assertSee('Install the optional driver');
        $this->post('/pushbox/send', ['class' => ExampleNotification::class, 'target_hash' => hash('sha256', 'test-token')])
            ->assertSessionHas('pushbox_send_result.ok', false)
            ->assertSessionHas('pushbox_send_result.note', 'laravel-notification-channels/fcm is not installed.');
    }
}
