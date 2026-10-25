<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Registry;

use Countable;
use Illuminate\Support\Collection;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, NotificationItem>
 */
final class NotificationGroup implements Countable, IteratorAggregate
{
    /** @var Collection<int, NotificationItem> */
    public readonly Collection $items;

    /** @param iterable<int, NotificationItem> $items */
    public function __construct(
        public readonly string $label,
        iterable $items = [],
    ) {
        $this->items = new Collection($items);
    }

    public function count(): int
    {
        return $this->items->count();
    }

    /** @return Traversable<int, NotificationItem> */
    public function getIterator(): Traversable
    {
        return $this->items->getIterator();
    }
}
