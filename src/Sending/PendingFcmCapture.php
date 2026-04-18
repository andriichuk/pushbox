<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Sending;

/**
 * Holds the FCM channel response while a Pushbox send is in progress.
 * Bound in the container only for the duration of {@see SendPushboxFcmNotificationJob::handle}.
 */
final class PendingFcmCapture
{
    public mixed $response = null;

    public float $startedAt = 0.0;

    public string $maskedTarget = '';

    public string $notificationClass = '';
}
