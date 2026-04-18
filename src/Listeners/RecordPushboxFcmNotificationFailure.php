<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Listeners;

use Andriichuk\Pushbox\Jobs\SendPushboxFcmNotificationJob;
use Andriichuk\Pushbox\Sending\FcmSendResponseFormatter;
use Andriichuk\Pushbox\Sending\PendingFcmCapture;
use Andriichuk\Pushbox\Sending\PushboxFcmSendOutcome;
use Illuminate\Notifications\Events\NotificationFailed;
use Throwable;

/**
 * Builds {@see PushboxController}'s send report when the FCM channel fails during a sync Pushbox send.
 */
final class RecordPushboxFcmNotificationFailure
{
    public function handle(NotificationFailed $event): void
    {
        if (! app()->bound(PendingFcmCapture::class)) {
            return;
        }

        if (! SendPushboxFcmNotificationJob::isFcmNotificationChannel($event->channel)) {
            return;
        }

        $capture = app(PendingFcmCapture::class);
        $exception = $event->data['exception'] ?? null;
        $sendReport = $event->data['report'] ?? null;

        if ($exception instanceof Throwable) {
            $message = $exception->getMessage();
            $detail = $exception->getTraceAsString();
        } elseif (is_object($sendReport)) {
            $message = PushboxFcmSendOutcome::failureMessageFromSendReportItem($sendReport);
            $detail = FcmSendResponseFormatter::toDisplayString($sendReport);
        } else {
            $message = 'Notification failed.';
            $detail = null;
        }

        $durationMs = $capture->startedAt > 0.0
            ? (int) round((microtime(true) - $capture->startedAt) * 1000)
            : null;

        app()->instance('pushbox.send_report', [
            'ok' => false,
            'queue_connection' => 'sync',
            'duration_ms' => $durationMs,
            'target' => $capture->maskedTarget !== '' ? $capture->maskedTarget : null,
            'notification_class' => $capture->notificationClass !== '' ? $capture->notificationClass : get_class($event->notification),
            'channel' => $event->channel,
            'response_text' => $detail,
            'note' => $message,
        ]);
    }
}
