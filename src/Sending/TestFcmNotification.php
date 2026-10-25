<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Sending;

use Illuminate\Notifications\Notification;

/**
 * Keep the registered recipient available to notification content while the FCM
 * channel receives an anonymous notifiable routed only to the test device.
 */
final class TestFcmNotification extends Notification
{
    public function __construct(
        private readonly Notification $notification,
        private readonly mixed $recipient,
    ) {}

    public function toFcm(mixed $target): mixed
    {
        if (! method_exists($this->notification, 'toFcm')) {
            throw new \RuntimeException('The notification must implement toFcm().');
        }

        return $this->notification->toFcm($this->recipient ?? $target);
    }

    public function shouldSend(mixed $target, string $channel): bool
    {
        return ! method_exists($this->notification, 'shouldSend')
            || $this->notification->shouldSend($this->recipient ?? $target, $channel);
    }
}
