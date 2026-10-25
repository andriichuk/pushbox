<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Tests\Feature;

use Andriichuk\Pushbox\Pushbox;
use Andriichuk\Pushbox\Tests\Fixtures\ExampleNotification;
use Andriichuk\Pushbox\Tests\Fixtures\FakeFcmMessage;
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
        app(Pushbox::class)->category('Orders')->label('Order shipped')->add(BrowserOrderNotification::class)
            ->variant('Long message', fn (): BrowserOrderNotification => new BrowserOrderNotification);
        app(Pushbox::class)->category('Payments')->label('Payment received')->add(ExampleNotification::class);
        config(['app.name' => 'Lunar Demo Store', 'pushbox.locales' => ['en' => 'English'], 'pushbox.send.enabled' => true, 'pushbox.send_allow_non_local' => true, 'pushbox.send.fcm.token' => 'browser-test-device']);
        file_put_contents($directory.'/notification.html', $this->get('/pushbox')->assertOk()->getContent());
        $otherNotification = route('pushbox.index', ['class' => ExampleNotification::class, 'locale' => 'en']);
        $this->get('/pushbox?'.http_build_query(['class' => BrowserOrderNotification::class, 'variant' => 'Long message', 'locale' => 'en']))
            ->assertOk()->assertSee($otherNotification);
        $this->get($otherNotification)->assertOk()->assertViewHas('previewError', null);
        config(['pushbox.send.fcm.token' => '']);
        file_put_contents($directory.'/no-device.html', $this->get('/pushbox')->assertOk()->getContent());
        config(['pushbox.send.fcm.token' => 'browser-test-device']);
        $report = [
            'ok' => false,
            'status' => 'failed',
            'note' => 'The registration token is not a valid FCM registration token',
            'target' => 'browser…device',
            'duration_ms' => 142,
            'notification_class' => BrowserOrderNotification::class,
            'channel' => 'fcm',
            'queue_connection' => 'sync',
            'response_text' => '{"error":"Invalid registration token"}',
        ];
        file_put_contents($directory.'/failed.html', $this->withSession(['pushbox_send_result' => $report])->get('/pushbox')->assertOk()->getContent());
        $report['ok'] = true;
        $report['status'] = 'accepted';
        $report['note'] = null;
        $report['response_text'] = '{"name":"projects/demo/messages/123"}';
        file_put_contents($directory.'/accepted.html', $this->withSession(['pushbox_send_result' => $report])->get('/pushbox')->assertOk()->getContent());
        file_put_contents($directory.'/saved-device.html', $this->withSession(['pushbox_send_result' => null, 'pushbox.fcm_device_token' => 'new-device'])->get('/pushbox')->assertOk()->getContent());
    }
}

class BrowserOrderNotification extends ExampleNotification
{
    public function toFcm(mixed $notifiable): FakeFcmMessage
    {
        return new FakeFcmMessage([
            'notification' => [
                'title' => 'Your order #LUN-2026-1042-GIFT-ORDER has shipped',
                'body' => 'DHL has your parcel. Estimated delivery: 2-3 business days. We will send another update when it reaches your local delivery office. Track your order for the latest updates.',
            ],
            'data' => ['event' => 'order.shipped', 'reference' => 'LUN-2026-1042-GIFT-ORDER'],
            'android' => ['ttl' => '86400s'],
        ]);
    }
}
