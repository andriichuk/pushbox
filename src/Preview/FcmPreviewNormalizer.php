<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Preview;

/**
 * Normalizes FCM message objects (e.g. laravel-notification-channels/fcm) for JSON/UI preview.
 */
class FcmPreviewNormalizer
{
    /**
     * @return array<string, mixed>
     */
    public function normalize(mixed $message): array
    {
        if (is_object($message) && method_exists($message, 'toArray')) {
            /** @var array<string, mixed> $arr */
            $arr = $message->toArray();

            return [
                'structured' => $arr,
                'json' => json_encode($arr, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ];
        }

        return [
            'structured' => ['value' => $message],
            'json' => json_encode($message, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ];
    }
}
