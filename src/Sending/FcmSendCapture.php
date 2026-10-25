<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Sending;

/**
 * Shared by Laravel's notification clones for one synchronous test send.
 */
final class FcmSendCapture
{
    public mixed $response = null;

    public ?string $failureMessage = null;
}
