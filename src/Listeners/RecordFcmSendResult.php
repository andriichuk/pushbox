<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Listeners;

use Andriichuk\Pushbox\Sending\PushboxFcmSendOutcome;
use Andriichuk\Pushbox\Sending\SendFcmNotification;
use Andriichuk\Pushbox\Sending\TestFcmNotification;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;
use Throwable;

final class RecordFcmSendResult
{
    public function handle(NotificationSent|NotificationFailed $event): void
    {
        if (! $event->notification instanceof TestFcmNotification || ! SendFcmNotification::isFcmNotificationChannel($event->channel)) {
            return;
        }

        $capture = $event->notification->capture;

        if ($event instanceof NotificationSent) {
            $capture->response = $event->response;

            return;
        }

        $exception = $event->data['exception'] ?? null;
        $sendReport = $event->data['report'] ?? null;

        if ($exception instanceof Throwable) {
            $capture->failureMessage = $exception->getMessage();
        } elseif (is_object($sendReport)) {
            $capture->failureMessage = PushboxFcmSendOutcome::failureMessageFromSendReportItem($sendReport);
            $capture->response ??= $sendReport;
        } else {
            $capture->failureMessage = 'Notification failed.';
        }
    }
}
