<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Tests\Unit;

use Andriichuk\Pushbox\Preview\PreviewResolver;
use Andriichuk\Pushbox\Pushbox;
use Andriichuk\Pushbox\Registry\NotificationItem;
use Andriichuk\Pushbox\Tests\Fixtures\ExampleNotification;
use Andriichuk\Pushbox\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PreviewLifecycleTest extends TestCase
{
    private function prepareDatabase(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'pushbox.database_rollback' => true]);
        DB::purge('sqlite');
        Schema::create('preview_rows', function ($table): void {
            $table->increments('id');
        });
    }

    public function test_factory_writes_are_rolled_back_after_success(): void
    {
        $this->prepareDatabase();
        $item = NotificationItem::make(function (): ExampleNotification {
            DB::table('preview_rows')->insert(['id' => 1]);

            return new ExampleNotification;
        }, null, null, null);
        app(PreviewResolver::class)->resolve($item);
        self::assertSame(0, DB::table('preview_rows')->count());
        self::assertSame(0, DB::transactionLevel());
    }

    public function test_factory_failure_rolls_back_and_restores_locale(): void
    {
        $this->prepareDatabase();
        app()->setLocale('en');
        app(Pushbox::class)->setLocale('uk');
        $item = NotificationItem::make(function (): ExampleNotification {
            self::assertSame('uk', app()->getLocale());
            DB::table('preview_rows')->insert(['id' => 1]);
            throw new \RuntimeException('Factory failed');
        }, null, null, null);
        try {
            app(PreviewResolver::class)->resolve($item);
            self::fail('Expected factory exception');
        } catch (\RuntimeException $exception) {
            self::assertSame('Factory failed', $exception->getMessage());
        }
        self::assertSame('en', app()->getLocale());
        self::assertSame(0, DB::table('preview_rows')->count());
        self::assertSame(0, DB::transactionLevel());
    }
}
