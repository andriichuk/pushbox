<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Jobs;

use Andriichuk\Pushbox\Pushbox;
use Andriichuk\Pushbox\Sending\FcmSendResponseFormatter;
use Andriichuk\Pushbox\Sending\PendingFcmCapture;
use Andriichuk\Pushbox\Sending\PushboxFcmSendOutcome;
use Andriichuk\Pushbox\Sending\TestFcmNotification;
use Andriichuk\Pushbox\Support\TokenMask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Traits\Localizable;

class SendPushboxFcmNotificationJob implements ShouldQueue
{
    use InteractsWithQueue, Localizable, Queueable;

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
        app()->forgetInstance('pushbox.send_report');

        if (! (bool) config('pushbox.send.enabled', false) || (! app()->isLocal() && ! (bool) config('pushbox.send_allow_non_local', false))) {
            throw new \RuntimeException('Pushbox test sending is disabled in this environment.');
        }

        $item = $pushbox->retrieve(
            $this->class,
            $this->variant,
            $this->locale,
            false,
        );

        if (! $item) {
            throw new \RuntimeException('Notification not found.');
        }

        if (! class_exists(self::FCM_CHANNEL)) {
            throw new \RuntimeException('laravel-notification-channels/fcm is not installed.');
        }

        $notifiable = Notification::route('fcm', $this->token);

        $capture = new PendingFcmCapture;
        $capture->startedAt = microtime(true);
        $capture->maskedTarget = TokenMask::mask($this->token);
        $capture->notificationClass = $this->class;
        app()->instance(PendingFcmCapture::class, $capture);

        try {
            $this->withLocale($pushbox->getLocale(), function () use ($item, $notifiable, $pushbox): void {
                $notification = $item->resolve(app());
                if (! method_exists($notification, 'toFcm')) {
                    throw new \RuntimeException('The notification must implement toFcm().');
                }

                $notification->locale($pushbox->getLocale());
                $testNotification = new TestFcmNotification($notification, $item->notifiable());
                $testNotification->locale($pushbox->getLocale());
                Notification::sendNow($notifiable, $testNotification, [self::FCM_CHANNEL]);
            });
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
                    'response_text' => is_string($mergedText) ? str_replace($this->token, TokenMask::mask($this->token), $mergedText) : null,
                    'note' => str_replace($this->token, TokenMask::mask($this->token), (string) ($existing['note'] ?? 'FCM rejected the message')),
                ]));

                return;
            }
        }

        $ok = PushboxFcmSendOutcome::isSuccessfulResponse($capture->response);
        $note = $ok ? null : (PushboxFcmSendOutcome::firstFailureMessage($capture->response) ?? 'FCM did not accept a message. The notification may have been skipped.');

        app()->instance('pushbox.send_report', [
            'ok' => $ok,
            'status' => $ok ? 'accepted' : ($capture->response === null ? 'skipped' : 'failed'),
            'queue_connection' => (string) ($this->connection ?? 'sync'),
            'duration_ms' => $durationMs,
            'target' => $capture->maskedTarget,
            'notification_class' => $this->class,
            'channel' => self::FCM_CHANNEL,
            'response_text' => str_replace($this->token, TokenMask::mask($this->token), $responseText),
            'note' => is_string($note) ? str_replace($this->token, TokenMask::mask($this->token), $note) : null,
        ]);
    }
}
