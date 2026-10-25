<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Sending;

use Andriichuk\Pushbox\Pushbox;
use Andriichuk\Pushbox\Support\TokenMask;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Traits\Localizable;
use RuntimeException;
use Throwable;

class SendFcmNotification
{
    use Localizable;

    public const FCM_CHANNEL = 'NotificationChannels\\Fcm\\FcmChannel';

    public function __construct(private readonly Pushbox $pushbox) {}

    public static function isFcmNotificationChannel(mixed $channel): bool
    {
        return is_string($channel)
            && ($channel === 'fcm' || $channel === self::FCM_CHANNEL || is_a($channel, self::FCM_CHANNEL, true));
    }

    /**
     * @return array{ok: bool, status: string, duration_ms: int, target: string, notification_class: string, channel: string, response_text: ?string, note: ?string}
     */
    public function send(string $token, string $class, ?string $variant = null, ?string $locale = null): array
    {
        $startedAt = microtime(true);
        $capture = new FcmSendCapture;

        try {
            $this->deliver($token, $class, $variant, $locale, $capture);

            $responseText = $capture->response !== null
                ? FcmSendResponseFormatter::toDisplayString($capture->response)
                : $capture->failureMessage;
            $successful = $capture->failureMessage === null && PushboxFcmSendOutcome::isSuccessfulResponse($capture->response);
            $note = $successful ? null : ($capture->failureMessage
                ?? PushboxFcmSendOutcome::firstFailureMessage($capture->response)
                ?? 'FCM did not accept a message. The notification may have been skipped.');
        } catch (Throwable $exception) {
            $successful = false;
            $note = $capture->failureMessage = $exception->getMessage();
            $responseText = $note;
        }

        $maskedTarget = TokenMask::mask($token);

        return [
            'ok' => $successful,
            'status' => $successful ? 'accepted' : ($capture->failureMessage !== null || $capture->response !== null ? 'failed' : 'skipped'),
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'target' => $maskedTarget,
            'notification_class' => $class,
            'channel' => self::FCM_CHANNEL,
            'response_text' => $responseText !== null ? str_replace($token, $maskedTarget, $responseText) : null,
            'note' => $note !== null ? str_replace($token, $maskedTarget, $note) : null,
        ];
    }

    private function deliver(string $token, string $class, ?string $variant, ?string $locale, FcmSendCapture $capture): void
    {
        if (! config('pushbox.send.enabled', false) || (! app()->isLocal() && ! config('pushbox.send_allow_non_local', false))) {
            throw new RuntimeException('Pushbox test sending is disabled in this environment.');
        }

        $item = $this->pushbox->retrieve($class, $variant, $locale);

        if ($item === null) {
            throw new RuntimeException('Notification not found.');
        }

        if (! class_exists(self::FCM_CHANNEL)) {
            throw new RuntimeException('laravel-notification-channels/fcm is not installed.');
        }

        $locale = $this->pushbox->getLocale();

        $this->withLocale($locale, function () use ($token, $item, $locale, $capture): void {
            $notification = clone $item->resolve(app());
            $notification->locale($locale);
            $testNotification = new TestFcmNotification($notification, $item->notifiable(), $capture);
            $testNotification->locale($locale);

            Notification::sendNow(Notification::route('fcm', $token), $testNotification, [self::FCM_CHANNEL]);
        });
    }
}
