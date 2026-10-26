<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Tests\Unit;

use Andriichuk\Pushbox\Pushbox;
use Andriichuk\Pushbox\Sending\SendFcmNotification;
use Andriichuk\Pushbox\Tests\Fixtures\ExampleNotification;
use Andriichuk\Pushbox\Tests\TestCase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;

class SendFcmNotificationTest extends TestCase
{
    /**
     * @return array<string, array{bool, bool}>
     */
    public static function disabledSendConfigurations(): array
    {
        return ['sending disabled' => [false, true], 'non-local sending disabled' => [true, false]];
    }

    #[DataProvider('disabledSendConfigurations')]
    public function test_direct_service_calls_respect_environment_guards(bool $enabled, bool $allowNonLocal): void
    {
        config(['pushbox.send.enabled' => $enabled, 'pushbox.send_allow_non_local' => $allowNonLocal]);
        Notification::shouldReceive('sendNow')->never();

        $report = app(SendFcmNotification::class)->send('test-device-token', ExampleNotification::class);

        self::assertFalse($report['ok']);
        self::assertSame('failed', $report['status']);
        self::assertSame('Pushbox test sending is disabled in this environment.', $report['note']);
    }

    public function test_unregistered_notifications_return_a_failed_report(): void
    {
        config(['pushbox.send.enabled' => true, 'pushbox.send_allow_non_local' => true]);
        Notification::shouldReceive('sendNow')->never();

        $report = app(SendFcmNotification::class)->send('test-device-token', ExampleNotification::class);

        self::assertFalse($report['ok']);
        self::assertSame('failed', $report['status']);
        self::assertSame('Notification not found.', $report['note']);
    }

    #[Group('fcm')]
    public function test_factory_failure_returns_a_redacted_report_and_restores_locale(): void
    {
        config(['pushbox.send.enabled' => true, 'pushbox.send_allow_non_local' => true]);
        app()->setLocale('en');
        app(Pushbox::class)->add(function (): ExampleNotification {
            self::assertSame('uk', app()->getLocale());
            throw new RuntimeException('Factory failed for test-device-token');
        });

        $report = app(SendFcmNotification::class)->send('test-device-token', ExampleNotification::class, locale: 'uk');

        self::assertSame('en', app()->getLocale());
        self::assertFalse($report['ok']);
        self::assertSame('failed', $report['status']);
        self::assertStringNotContainsString('test-device-token', json_encode($report));
        self::assertStringContainsString('Factory failed', $report['note']);
        self::assertGreaterThanOrEqual(0, $report['duration_ms']);
    }
}
