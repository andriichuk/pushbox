<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Tests\Fixtures;

/**
 * Minimal stand-in for laravel-notification-channels/fcm payloads in tests (no Firebase stack).
 */
final class FakeFcmMessage
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        private array $payload,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->payload;
    }
}
