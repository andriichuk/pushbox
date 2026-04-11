<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Andriichuk\Pushbox\Registry\NotificationItem add(\Closure|string $factory)
 * @method static \Andriichuk\Pushbox\Registry\NotifyRegistrar label(string $label)
 * @method static \Andriichuk\Pushbox\Registry\NotifyRegistrar category(string $category)
 * @method static \Andriichuk\Pushbox\Registry\NotifyRegistrar to(mixed $notifiable)
 * @method static \Illuminate\Support\Collection notifications()
 * @method static \Illuminate\Support\Collection groupedNotifications()
 * @method static \Andriichuk\Pushbox\Registry\NotificationItem|null retrieve(?string $class, ?string $variant, ?string $locale, bool $fallback = false)
 * @method static array previewPayload(\Andriichuk\Pushbox\Registry\NotificationItem $item)
 *
 * @see \Andriichuk\Pushbox\Pushbox
 */
class Pushbox extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'pushbox';
    }
}
