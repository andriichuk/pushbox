<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Jobs;

use Andriichuk\Pushbox\Pushbox;
use Andriichuk\Pushbox\Sending\FcmSendResponseFormatter;
use Andriichuk\Pushbox\Sending\PendingFcmCapture;
use Andriichuk\Pushbox\Sending\PushboxFcmSendOutcome;
use Andriichuk\Pushbox\Support\TokenMask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendPushboxFcmNotificationJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public const FCM_CHANNEL = 'NotificationChannels\\Fcm\\FcmChannel';

    /**
     * Channel name passed on {@see NotificationSent}
     * / {@see NotificationFailed} — often {@code 'fcm'} from {@code via()}.
     */
    public static function isFcmNotificationChannel(mixed $channel): bool
    {
        if (! is_string($channel)) {
            return false;
        }

        if ($channel === self::FCM_CHANNEL) {
            return true;
        }

        if ($channel === 'fcm') {
            return true;
        }

        return is_a($channel, self::FCM_CHANNEL, true);
    }

    public function __construct(
        public string $token,
        public string $class,
        public ?string $variant,
        public ?string $locale,
    ) {}

    public function handle(Pushbox $pushbox): void
    {
        $item = $pushbox->retrieve(
            $this->class,
            $this->variant,
            $this->locale,
            false,
        );

        if (! $item) {
            throw new \RuntimeException('Notification not found.');
        }

        $notification = $item->resolve(app());

        if (! class_exists(self::FCM_CHANNEL)) {
            throw new \RuntimeException('laravel-notification-channels/fcm is not installed.');
        }

        $notifiable = Notification::route(self::FCM_CHANNEL, $this->token);

        $capture = new PendingFcmCapture;
        $capture->startedAt = microtime(true);
        $capture->maskedTarget = TokenMask::mask($this->token);
        $capture->notificationClass = $this->class;
        app()->instance(PendingFcmCapture::class, $capture);

        try {
            Notification::sendNow($notifiable, $notification, [self::FCM_CHANNEL]);
        } finally {
            app()->forgetInstance(PendingFcmCapture::class);
        }

        $durationMs = (int) round((microtime(true) - $capture->startedAt) * 1000);

        $responseText = FcmSendResponseFormatter::toDisplayString($capture->response);

        if (app()->bound('pushbox.send_report')) {
            $existing = app('pushbox.send_report');
            if (is_array($existing) && ($existing['ok'] ?? true) === false) {
                app()->forgetInstance('pushbox.send_report');
                $mergedText = $capture->response !== null
                    ? $responseText
                    : ($existing['response_text'] ?? $responseText);
                app()->instance('pushbox.send_report', array_merge($existing, [
                    'duration_ms' => $existing['duration_ms'] ?? $durationMs,
                    'response_text' => $mergedText,
                ]));

                return;
            }
        }

        $ok = PushboxFcmSendOutcome::isSuccessfulResponse($capture->response);
        $note = $ok ? null : (PushboxFcmSendOutcome::firstFailureMessage($capture->response) ?? 'FCM reported a send failure.');

        app()->instance('pushbox.send_report', [
            'ok' => $ok,
            'queue_connection' => (string) ($this->connection ?? 'sync'),
            'duration_ms' => $durationMs,
            'target' => $capture->maskedTarget,
            'notification_class' => $this->class,
            'channel' => self::FCM_CHANNEL,
            'response_text' => $responseText,
            'note' => $note,
        ]);
    }
}
