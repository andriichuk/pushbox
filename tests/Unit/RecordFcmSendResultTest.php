<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Tests\Unit;

use Andriichuk\Pushbox\Sending\FcmSendCapture;
use Andriichuk\Pushbox\Sending\SendFcmNotification;
use Andriichuk\Pushbox\Sending\TestFcmNotification;
use Andriichuk\Pushbox\Tests\Fixtures\ExampleNotification;
use Andriichuk\Pushbox\Tests\TestCase;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use stdClass;

class RecordFcmSendResultTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function fcmChannels(): array
    {
        return ['class name' => [SendFcmNotification::FCM_CHANNEL], 'alias' => ['fcm']];
    }

    #[DataProvider('fcmChannels')]
    public function test_sent_events_update_the_capture_shared_by_notification_clones(string $channel): void
    {
        $capture = new FcmSendCapture;
        $notification = new TestFcmNotification(new ExampleNotification, null, $capture);

        Event::dispatch(new NotificationSent(new stdClass, clone $notification, $channel, 'Provider response'));

        self::assertSame('Provider response', $capture->response);
        self::assertNull($capture->failureMessage);
    }

    #[DataProvider('fcmChannels')]
    public function test_failed_events_record_the_provider_exception(string $channel): void
    {
        $capture = new FcmSendCapture;
        $notification = new TestFcmNotification(new ExampleNotification, null, $capture);

        Event::dispatch(new NotificationFailed(new stdClass, clone $notification, $channel, [
            'exception' => new RuntimeException('Provider unavailable'),
        ]));

        self::assertSame('Provider unavailable', $capture->failureMessage);
        self::assertNull($capture->response);
    }

    public function test_other_channels_do_not_change_the_capture(): void
    {
        $capture = new FcmSendCapture;
        $notification = new TestFcmNotification(new ExampleNotification, null, $capture);

        Event::dispatch(new NotificationSent(new stdClass, $notification, 'mail', 'Mail response'));
        Event::dispatch(new NotificationFailed(new stdClass, $notification, 'mail', ['exception' => new RuntimeException('Mail failed')]));

        self::assertNull($capture->response);
        self::assertNull($capture->failureMessage);
    }

    public function test_unrelated_notifications_do_not_change_another_sends_capture(): void
    {
        $capture = new FcmSendCapture;
        $notification = new TestFcmNotification(new ExampleNotification, null, $capture);
        $otherCapture = new FcmSendCapture;
        $otherNotification = new TestFcmNotification(new ExampleNotification, null, $otherCapture);

        Event::dispatch(new NotificationSent(new stdClass, clone $notification, 'fcm', 'Original response'));
        Event::dispatch(new NotificationSent(new stdClass, new ExampleNotification, 'fcm', 'Ordinary response'));
        Event::dispatch(new NotificationFailed(new stdClass, new ExampleNotification, 'fcm', ['exception' => new RuntimeException('Unrelated failure')]));
        Event::dispatch(new NotificationSent(new stdClass, clone $otherNotification, 'fcm', 'Another test response'));
        Event::dispatch(new NotificationFailed(new stdClass, clone $otherNotification, 'fcm', ['exception' => new RuntimeException('Another test failed')]));

        self::assertSame('Original response', $capture->response);
        self::assertNull($capture->failureMessage);
        self::assertSame('Another test response', $otherCapture->response);
        self::assertSame('Another test failed', $otherCapture->failureMessage);
    }

    public function test_failure_without_provider_details_is_reported(): void
    {
        $capture = new FcmSendCapture;
        $notification = new TestFcmNotification(new ExampleNotification, null, $capture);

        Event::dispatch(new NotificationFailed(new stdClass, $notification, 'fcm'));

        self::assertSame('Notification failed.', $capture->failureMessage);
        self::assertNull($capture->response);
    }
}
