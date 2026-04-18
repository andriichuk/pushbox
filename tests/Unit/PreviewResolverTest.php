<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Tests\Unit;

use Andriichuk\Pushbox\Preview\PreviewResolver;
use Andriichuk\Pushbox\Registry\NotificationItem;
use Andriichuk\Pushbox\Tests\Fixtures\ExampleNotification;
use Andriichuk\Pushbox\Tests\TestCase;

class PreviewResolverTest extends TestCase
{
    public function test_resolves_fcm(): void
    {
        /** @var PreviewResolver $resolver */
        $resolver = $this->app->make(PreviewResolver::class);

        $item = NotificationItem::make(ExampleNotification::class, null, null, new \stdClass);
        $payload = $resolver->resolve($item);

        $this->assertNotNull($payload['fcm']);
        $this->assertArrayHasKey('json', $payload['fcm']);
        $this->assertSame('Hi', $payload['fcm']['display']['title'] ?? null);
        $this->assertSame('Hello', $payload['fcm']['display']['body'] ?? null);
        $this->assertArrayNotHasKey('sms', $payload);
    }
}
