<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Tests\Integration;

use Andriichuk\Pushbox\Jobs\SendPushboxFcmNotificationJob;
use Andriichuk\Pushbox\Pushbox;
use Andriichuk\Pushbox\Sending\PendingFcmCapture;
use Andriichuk\Pushbox\Tests\TestCase;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\Messaging\InvalidMessage;
use Kreait\Firebase\Messaging\MessageTarget;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Kreait\Firebase\Messaging\SendReport;
use Mockery;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;
use PHPUnit\Framework\Attributes\Group;

#[Group('fcm')]
class FcmSendingTest extends TestCase
{
    private function sendData(string $locale = 'en'): array
    {
        return ['class' => TypedNotification::class, 'locale' => $locale, 'target_hash' => hash('sha256', 'test-device-token')];
    }

    private function prepare(): array
    {
        config(['pushbox.send.enabled' => true, 'pushbox.send_allow_non_local' => true, 'pushbox.send.fcm.token' => 'test-device-token', 'pushbox.locales' => ['en' => 'English', 'uk' => 'Ukrainian']]);
        app()->setLocale('en');
        app('translator')->addLines(['push.title' => 'Hello'], 'en');
        app('translator')->addLines(['push.title' => 'Вітаю'], 'uk');
        $recipient = new TestRecipient(['name' => 'Ada']);
        $book = app(Pushbox::class);
        $book->to($recipient)->add(fn (): TypedNotification => new TypedNotification(app()->getLocale()));
        $client = Mockery::mock(Messaging::class);
        app()->instance(FcmChannel::class, new FcmChannel(app(Dispatcher::class), $client));

        return [$book, $recipient, $client];
    }

    public function test_real_driver_uses_only_test_token_and_matches_translated_preview(): void
    {
        [$book, $recipient, $client] = $this->prepare();
        $item = $book->retrieve(TypedNotification::class, null, 'uk');
        $preview = $book->previewPayload($item)['fcm']['structured'];
        self::assertSame('en', app()->getLocale());
        $client->shouldReceive('sendMulticast')->once()->withArgs(function ($message, $tokens) use ($preview): bool {
            self::assertSame(['test-device-token'], $tokens);
            self::assertSame($preview, $message->toArray());
            self::assertSame('Вітаю', $message->toArray()['notification']['title']);
            self::assertSame('Ada uk uk', $message->toArray()['notification']['body']);

            return true;
        })->andReturn(MulticastSendReport::withItems([
            SendReport::success(MessageTarget::with('token', 'test-device-token'), ['name' => 'projects/test/messages/123']),
        ]));
        Log::spy();
        $this->post('/pushbox/send', $this->sendData('uk'))
            ->assertSessionHas('pushbox_send_result.ok', true)
            ->assertSessionHas('pushbox_send_result.status', 'accepted')
            ->assertSessionHas('status', 'Accepted by FCM. Device delivery is not guaranteed.');
        self::assertSame('en', app()->getLocale());
        self::assertSame(['production-device'], $recipient->routeNotificationFor('fcm'));
        self::assertStringContainsString('projects/test/messages/123', session('pushbox_send_result')['response_text']);
        self::assertFalse(app()->bound(PendingFcmCapture::class));
        self::assertFalse(app()->bound('pushbox.send_report'));
        Log::shouldHaveReceived('log')->once()->with('info', 'pushbox.accepted', Mockery::type('array'));
    }

    public function test_provider_rejection_is_failed_and_redacts_device_token(): void
    {
        [, , $client] = $this->prepare();
        $client->shouldReceive('sendMulticast')->once()->andReturn(MulticastSendReport::withItems([
            SendReport::failure(MessageTarget::with('token', 'test-device-token'), new InvalidMessage('Invalid token test-device-token')),
        ]));
        Log::spy();
        $this->post('/pushbox/send', $this->sendData())
            ->assertSessionHas('pushbox_send_result.ok', false)
            ->assertSessionHas('pushbox_send_result.status', 'failed')
            ->assertSessionMissing('status');
        self::assertStringNotContainsString('test-device-token', json_encode(session('pushbox_send_result')));
        Log::shouldHaveReceived('log')->once()->with('warning', 'pushbox.failed', Mockery::type('array'));
    }

    public function test_provider_exception_restores_locale_and_cleans_capture(): void
    {
        [, , $client] = $this->prepare();
        $client->shouldReceive('sendMulticast')->once()->andThrow(new \RuntimeException('Provider unavailable'));
        $this->post('/pushbox/send', $this->sendData('uk'))
            ->assertSessionHas('pushbox_send_result.ok', false)
            ->assertSessionHas('pushbox_send_result.note', 'Provider unavailable');
        self::assertSame('en', app()->getLocale());
        self::assertFalse(app()->bound(PendingFcmCapture::class));
    }

    public function test_notification_veto_is_skipped_and_never_calls_firebase(): void
    {
        [$book, $recipient, $client] = $this->prepare();
        $book->to($recipient)->add(fn (): VetoNotification => new VetoNotification('en'));
        $client->shouldNotReceive('sendMulticast');
        (new SendPushboxFcmNotificationJob('test-device-token', VetoNotification::class, null, 'uk'))->handle($book);
        self::assertFalse(app('pushbox.send_report')['ok']);
        self::assertSame('skipped', app('pushbox.send_report')['status']);
        self::assertSame('en', app()->getLocale());
    }
}

class TestRecipient extends Model
{
    use Notifiable;

    protected $guarded = [];

    public function routeNotificationForFcm(): array
    {
        return ['production-device'];
    }
}

class TypedNotification extends Notification
{
    public function __construct(private string $constructedLocale) {}

    public function toFcm(TestRecipient $recipient): FcmMessage
    {
        return new FcmMessage(notification: new FcmNotification(title: __('push.title'), body: $recipient->name.' '.$this->constructedLocale.' '.$this->locale));
    }
}

class VetoNotification extends TypedNotification
{
    public function shouldSend(TestRecipient $recipient, string $channel): bool
    {
        return false;
    }
}
