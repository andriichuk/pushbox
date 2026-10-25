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
            /** @var array<string, mixed> $structured */
            $structured = $message->toArray();

            return [
                'structured' => $structured,
                'json' => json_encode($structured, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'display' => FcmDisplayExtractor::fromStructured($structured),
            ];
        }

        return [
            'structured' => ['value' => $message],
            'json' => json_encode($message, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'display' => [
                'title' => null,
                'body' => null,
                'image' => null,
            ],
        ];
    }
}
