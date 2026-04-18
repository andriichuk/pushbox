<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Tests\Unit;

use Andriichuk\Pushbox\Jobs\SendPushboxFcmNotificationJob;
use Andriichuk\Pushbox\Listeners\RecordPushboxFcmNotificationFailure;
use Andriichuk\Pushbox\Sending\PendingFcmCapture;
use Andriichuk\Pushbox\Tests\TestCase;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Notification;
use PHPUnit\Framework\Attributes\Test;

class RecordPushboxFcmNotificationFailureTest extends TestCase
{
    #[Test]
    public function it_records_send_report_when_fcm_notification_fails(): void
    {
        $capture = new PendingFcmCapture;
        $capture->startedAt = microtime(true) - 0.1;
        $capture->maskedTarget = 'masked…token';
        $capture->notificationClass = 'App\\Notifications\\Example';

        $this->app->instance(PendingFcmCapture::class, $capture);

        $notification = new class extends Notification
        {
            /**
             * @return array<int, string>
             */
            public function via(object $notifiable): array
            {
                return [];
            }
        };

        $listener = new RecordPushboxFcmNotificationFailure;
        $listener->handle(new NotificationFailed(
            new \stdClass,
            $notification,
            SendPushboxFcmNotificationJob::FCM_CHANNEL,
            ['exception' => new \RuntimeException('FCM rejected token')]
        ));

        $report = $this->app->make('pushbox.send_report');
        $this->assertIsArray($report);
        $this->assertFalse($report['ok']);
        $this->assertSame('FCM rejected token', $report['note']);
        $this->assertSame('App\\Notifications\\Example', $report['notification_class']);
        $this->assertSame('masked…token', $report['target']);
        $this->assertIsString($report['response_text']);
        $this->assertNotSame('', $report['response_text']);
    }

    #[Test]
    public function it_ignores_when_pending_capture_is_not_bound(): void
    {
        $notification = new class extends Notification
        {
            /**
             * @return array<int, string>
             */
            public function via(object $notifiable): array
            {
                return [];
            }
        };

        $listener = new RecordPushboxFcmNotificationFailure;
        $listener->handle(new NotificationFailed(
            new \stdClass,
            $notification,
            SendPushboxFcmNotificationJob::FCM_CHANNEL,
            ['exception' => new \RuntimeException('x')]
        ));

        $this->assertFalse($this->app->bound('pushbox.send_report'));
    }

    #[Test]
    public function it_ignores_non_fcm_channels(): void
    {
        $this->app->instance(PendingFcmCapture::class, new PendingFcmCapture);

        $notification = new class extends Notification
        {
            /**
             * @return array<int, string>
             */
            public function via(object $notifiable): array
            {
                return [];
            }
        };

        $listener = new RecordPushboxFcmNotificationFailure;
        $listener->handle(new NotificationFailed(
            new \stdClass,
            $notification,
            'mail',
            ['exception' => new \RuntimeException('x')]
        ));

        $this->assertFalse($this->app->bound('pushbox.send_report'));
    }

    #[Test]
    public function it_records_when_channel_alias_is_fcm(): void
    {
        $capture = new PendingFcmCapture;
        $capture->startedAt = microtime(true);
        $capture->maskedTarget = 'm…t';
        $capture->notificationClass = 'App\\N\\X';

        $this->app->instance(PendingFcmCapture::class, $capture);

        $notification = new class extends Notification
        {
            /**
             * @return array<int, string>
             */
            public function via(object $notifiable): array
            {
                return [];
            }
        };

        $sendReport = new class
        {
            public function isFailure(): bool
            {
                return true;
            }

            public function error(): \RuntimeException
            {
                return new \RuntimeException('bad token');
            }
        };

        $listener = new RecordPushboxFcmNotificationFailure;
        $listener->handle(new NotificationFailed(
            new \stdClass,
            $notification,
            'fcm',
            ['report' => $sendReport]
        ));

        $report = $this->app->make('pushbox.send_report');
        $this->assertIsArray($report);
        $this->assertFalse($report['ok']);
        $this->assertSame('bad token', $report['note']);
    }
}
