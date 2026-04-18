<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Preview;

/**
 * Pulls human-readable notification title/body (and optional image) from common FCM / Laravel-FCM array shapes.
 */
final class FcmDisplayExtractor
{
    /**
     * @param  array<string, mixed>  $payload  Root array from FcmMessage::toArray() or similar
     * @return array{title: ?string, body: ?string, image: ?string}
     */
    public static function fromStructured(array $payload): array
    {
        $title = self::firstString([
            self::dig($payload, ['notification', 'title']),
            self::dig($payload, ['message', 'notification', 'title']),
            self::dig($payload, ['android', 'notification', 'title']),
            self::dig($payload, ['webpush', 'notification', 'title']),
            self::dig($payload, ['data', 'title']),
            self::dig($payload, ['data', 'gcm.notification.title']),
        ]);

        $body = self::firstString([
            self::dig($payload, ['notification', 'body']),
            self::dig($payload, ['message', 'notification', 'body']),
            self::dig($payload, ['android', 'notification', 'body']),
            self::dig($payload, ['webpush', 'notification', 'body']),
            self::dig($payload, ['data', 'body']),
            self::dig($payload, ['data', 'gcm.notification.body']),
        ]);

        $image = self::firstString([
            self::dig($payload, ['notification', 'image']),
            self::dig($payload, ['message', 'notification', 'image']),
            self::dig($payload, ['android', 'notification', 'image']),
            self::dig($payload, ['data', 'image']),
        ]);

        return [
            'title' => $title,
            'body' => $body,
            'image' => $image,
        ];
    }

    /**
     * @param  array<int, mixed>  $path
     */
    private static function dig(mixed $root, array $path): mixed
    {
        $cur = $root;
        foreach ($path as $key) {
            if (! is_array($cur) || ! array_key_exists($key, $cur)) {
                return null;
            }
            $cur = $cur[$key];
        }

        return $cur;
    }

    /**
     * @param  array<int, mixed>  $candidates
     */
    private static function firstString(array $candidates): ?string
    {
        foreach ($candidates as $v) {
            if (is_string($v) && $v !== '') {
                return $v;
            }
        }

        return null;
    }
}
