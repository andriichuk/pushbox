<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Tests\Unit;

use Andriichuk\Pushbox\Registry\NotificationGroup;
use Andriichuk\Pushbox\Registry\NotificationItem;
use Andriichuk\Pushbox\Tests\Fixtures\ExampleNotification;
use Andriichuk\Pushbox\Tests\TestCase;
use Illuminate\Support\Collection;

class NotificationGroupTest extends TestCase
{
    public function test_group_remains_countable_and_iterable(): void
    {
        $item = NotificationItem::make(ExampleNotification::class, null, null, null);
        $group = new NotificationGroup('Orders', [$item]);

        self::assertSame('Orders', $group->label);
        self::assertCount(1, $group);
        self::assertSame([$item], iterator_to_array($group));
    }

    public function test_group_items_support_standard_collection_operations(): void
    {
        $item = NotificationItem::make(ExampleNotification::class, null, null, null);
        $group = new NotificationGroup('Orders', new Collection([4 => $item]));

        self::assertSame([$item], $group->items->values()->all());
        self::assertSame([4 => ExampleNotification::class], $group->items->map(fn (NotificationItem $notification): string => $notification->className())->all());
        self::assertSame([], $group->items->filter(fn (): bool => false)->all());
        self::assertSame([4 => $item], $group->items->all());
        self::assertSame('Orders', $group->label);
    }
}
