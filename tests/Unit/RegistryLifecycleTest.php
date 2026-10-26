<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Tests\Unit;

use Andriichuk\Pushbox\Facades\Pushbox;
use Andriichuk\Pushbox\Registry\NotificationItem;
use Andriichuk\Pushbox\Tests\Fixtures\ExampleNotification;
use Andriichuk\Pushbox\Tests\TestCase;
use RuntimeException;

class RegistryLifecycleTest extends TestCase
{
    public function test_nested_groups_restore_outer_context_even_when_inner_throws(): void
    {
        Pushbox::category('Outer')->group(function (): void {
            Pushbox::add(ExampleNotification::class);

            try {
                Pushbox::category('Inner')->group(function (): void {
                    Pushbox::add(ExampleNotification::class);
                    throw new RuntimeException('Broken group');
                });
            } catch (RuntimeException) {
            }
            Pushbox::add(ExampleNotification::class);
        });
        Pushbox::add(ExampleNotification::class);
        self::assertSame(['Outer', 'Inner', 'Outer', null], Pushbox::notifications()->map(fn ($item) => $item->getCategory())->all());
        self::assertCount(3, Pushbox::groupedNotifications());
    }

    public function test_variant_resolution_does_not_mutate_registered_item(): void
    {
        $base = Pushbox::add(ExampleNotification::class);
        $variant = new ExampleNotification;
        $base->variant('alternative', fn () => $variant);
        $selected = Pushbox::retrieve(ExampleNotification::class, 'alternative', null);
        self::assertSame($variant, $selected->resolve(app()));
        self::assertNull($base->selectedVariant());
        $default = Pushbox::retrieve(ExampleNotification::class, null, null);
        self::assertNotSame($variant, $default->resolve(app()));
    }

    public function test_unknown_variant_is_diagnosed_instead_of_sending_default(): void
    {
        $item = NotificationItem::make(ExampleNotification::class, null, null, null);
        $this->expectExceptionMessage('Unknown notification variant');
        $item->selectVariant('missing');
    }

    public function test_invalid_locale_cannot_reuse_previous_selection(): void
    {
        config(['pushbox.locales' => ['en' => 'English', 'uk' => 'Ukrainian']]);
        Pushbox::add(ExampleNotification::class);
        Pushbox::retrieve(ExampleNotification::class, null, 'uk');
        Pushbox::retrieve(ExampleNotification::class, null, 'not-allowed');
        self::assertSame('en', app(\Andriichuk\Pushbox\Pushbox::class)->getLocale());
    }
}
