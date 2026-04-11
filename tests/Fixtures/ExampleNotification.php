<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Tests\Fixtures;

use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotificationResource;

class ExampleNotification extends Notification
{
    /**
     * @return array<int, class-string>
     */
    public function via(object $notifiable): array
    {
        return [FcmChannel::class];
    }

    public function toFcm(mixed $notifiable): FcmMessage
    {
        return (new FcmMessage)
            ->data(['foo' => 'bar'])
            ->notification(new FcmNotificationResource(title: 'Hi', body: 'Hello'));
    }
}
