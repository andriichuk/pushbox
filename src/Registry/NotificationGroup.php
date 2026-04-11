<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Registry;

use Illuminate\Support\Collection;

/**
 * @extends Collection<int, NotificationItem>
 */
class NotificationGroup extends Collection
{
    public function __construct(
        public readonly string $label,
        $items = [],
    ) {
        parent::__construct($items);
    }
}
