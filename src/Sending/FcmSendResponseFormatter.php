<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Sending;

use Andriichuk\Pushbox\Support\TokenMask;
use Illuminate\Support\Collection;
use JsonSerializable;
use Stringable;
use Throwable;

final class FcmSendResponseFormatter
{
    public static function toDisplayString(mixed $response): string
    {
        if ($response === null) {
            return '(no response from channel)';
        }

        if (is_string($response)) {
            return $response;
        }

        if (is_scalar($response)) {
            return (string) json_encode($response, JSON_UNESCAPED_UNICODE);
        }

        if (is_array($response)) {
            return self::jsonPretty($response);
        }

        if (is_object($response) && method_exists($response, 'getItems')) {
            return self::jsonPretty(array_map(fn ($report) => self::sendReportData($report), $response->getItems()));
        }

        if (is_object($response) && method_exists($response, 'target') && method_exists($response, 'isFailure')) {
            return self::jsonPretty(self::sendReportData($response));
        }

        if ($response instanceof Collection) {
            return self::jsonPretty($response->map(fn ($report) => json_decode(self::toDisplayString($report), true))->all());
        }

        if ($response instanceof JsonSerializable) {
            try {
                return self::toDisplayString($response->jsonSerialize());
            } catch (Throwable) {
                // fall through
            }
        }

        if (is_object($response)) {
            if (method_exists($response, 'json')) {
                try {
                    $data = $response->json();

                    return is_string($data) ? $data : self::toDisplayString($data);
                } catch (Throwable) {
                    // fall through
                }
            }

            if (method_exists($response, 'body')) {
                try {
                    return (string) $response->body();
                } catch (Throwable) {
                    // fall through
                }
            }

            if ($response instanceof Stringable) {
                return (string) $response;
            }

            try {
                $encoded = json_encode($response, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
                $decoded = json_decode($encoded, true, 512, JSON_THROW_ON_ERROR);

                return is_array($decoded) ? self::jsonPretty($decoded) : $encoded;
            } catch (Throwable) {
                return '('.get_class($response).')';
            }
        }

        return (string) $response;
    }

    /** @return array<string, mixed> */
    private static function sendReportData(object $report): array
    {
        $error = $report->error();

        return [
            'accepted' => ! $report->isFailure(),
            'target' => TokenMask::mask($report->target()->value()),
            'result' => $report->result(),
            'error' => $error instanceof Throwable ? $error->getMessage() : null,
        ];
    }

    /**
     * @param  array<mixed>  $data
     */
    private static function jsonPretty(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
