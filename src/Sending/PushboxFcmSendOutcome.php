<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Sending;

use Illuminate\Support\Collection;
use Throwable;

/**
 * Interprets FCM channel responses (Kreait MulticastSendReport / SendReport) for Pushbox reporting.
 */
final class PushboxFcmSendOutcome
{
    /**
     * @param  mixed  $response  Value passed to {@see NotificationSent::$response} for the FCM channel.
     */
    public static function isSuccessfulResponse(mixed $response): bool
    {
        if ($response === null) {
            return false;
        }

        if ($response instanceof Collection) {
            foreach ($response as $chunkReport) {
                if (! self::multicastReportIsFullySuccessful($chunkReport)) {
                    return false;
                }
            }

            return true;
        }

        return self::multicastReportIsFullySuccessful($response);
    }

    public static function firstFailureMessage(mixed $response): ?string
    {
        if ($response instanceof Collection) {
            foreach ($response as $chunkReport) {
                $msg = self::firstFailureMessageFromMulticast($chunkReport);
                if ($msg !== null) {
                    return $msg;
                }
            }

            return null;
        }

        return self::firstFailureMessageFromMulticast($response);
    }

    private static function multicastReportIsFullySuccessful(mixed $report): bool
    {
        if (! is_object($report) || ! method_exists($report, 'getItems')) {
            return true;
        }

        foreach ($report->getItems() as $item) {
            if (self::sendReportIsFailure($item)) {
                return false;
            }
        }

        return true;
    }

    private static function firstFailureMessageFromMulticast(mixed $report): ?string
    {
        if (! is_object($report) || ! method_exists($report, 'getItems')) {
            return null;
        }

        foreach ($report->getItems() as $item) {
            if (self::sendReportIsFailure($item)) {
                return self::failureMessageFromSendReportItem($item);
            }
        }

        return null;
    }

    private static function sendReportIsFailure(mixed $item): bool
    {
        return is_object($item) && method_exists($item, 'isFailure') && $item->isFailure();
    }

    public static function failureMessageFromSendReportItem(object $sendReport): string
    {
        return self::messageFromSendReport($sendReport);
    }

    private static function messageFromSendReport(object $sendReport): string
    {
        if (method_exists($sendReport, 'error')) {
            try {
                $error = $sendReport->error();
                if ($error instanceof Throwable) {
                    return $error->getMessage();
                }
                if (is_string($error) && $error !== '') {
                    return $error;
                }
                if (is_object($error) && method_exists($error, 'getMessage')) {
                    return (string) $error->getMessage();
                }
            } catch (Throwable) {
                // fall through
            }
        }

        if (method_exists($sendReport, 'message')) {
            try {
                $m = $sendReport->message();
                if (is_string($m) && $m !== '') {
                    return $m;
                }
            } catch (Throwable) {
                // fall through
            }
        }

        return 'FCM reported a send failure for this token.';
    }
}
