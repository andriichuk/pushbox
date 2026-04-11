<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Tests\Fixtures;

use Illuminate\Notifications\Notification;

class ExampleNotification extends Notification
{
    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [];
    }

    public function toFcm(mixed $notifiable): FakeFcmMessage
    {
        return new FakeFcmMessage([
            'notification' => [
                'title' => 'Hi',
                'body' => 'Hello',
            ],
            'data' => [
                'foo' => 'bar',
            ],
        ]);
    }
}
