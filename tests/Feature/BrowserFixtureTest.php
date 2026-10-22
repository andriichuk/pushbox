<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Tests\Feature;

use Andriichuk\Pushbox\Pushbox;
use Andriichuk\Pushbox\Tests\Fixtures\ExampleNotification;
use Andriichuk\Pushbox\Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('browser-fixture')]
class BrowserFixtureTest extends TestCase
{
    public function test_render_browser_fixtures(): void
    {
        $directory = dirname(__DIR__, 2).'/build/browser';
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        $empty = $this->get('/pushbox')->assertOk();
        file_put_contents($directory.'/empty.html', $empty->getContent());
        app(Pushbox::class)->add(ExampleNotification::class);
        config(['pushbox.send.enabled' => true, 'pushbox.send_allow_non_local' => true, 'pushbox.send.fcm.token' => 'browser-test-device']);
        $response = $this->get('/pushbox')->assertOk();
        file_put_contents($directory.'/notification.html', $response->getContent());
    }
}
