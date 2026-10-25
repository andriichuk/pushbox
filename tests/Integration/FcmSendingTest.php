<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Tests\Integration;

use Andriichuk\Pushbox\Pushbox;
use Andriichuk\Pushbox\Sending\SendFcmNotification;
use Andriichuk\Pushbox\Sending\TestFcmNotification;
use Andriichuk\Pushbox\Tests\TestCase;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Facades\Queue;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\Messaging\InvalidMessage;
use Kreait\Firebase\Messaging\MessageTarget;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Kreait\Firebase\Messaging\SendReport;
use Mockery;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;

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
        Log::shouldHaveReceived('log')->once()->with('info', 'pushbox.accepted', Mockery::type('array'));
    }

    public function test_provider_rejection_is_failed_and_redacts_device_token(): void
    {
        [, , $client] = $this->prepare();
        $client->shouldReceive('sendMulticast')->once()->andReturn(MulticastSendReport::withItems([
            SendReport::failure(MessageTarget::with('token', 'test-device-token'), new InvalidMessage('Invalid token test-device-token')),
        ]));
        Log::spy();
        $this->withHeaders(['Accept' => 'text/html', 'X-Requested-With' => 'XMLHttpRequest'])
            ->post('/pushbox/send', $this->sendData())
            ->assertSessionHas('pushbox_send_result.ok', false)
            ->assertSessionHas('pushbox_send_result.status', 'failed')
            ->assertSessionMissing('status');
        self::assertStringNotContainsString('test-device-token', json_encode(session('pushbox_send_result')));
        Log::shouldHaveReceived('log')->once()->with('warning', 'pushbox.failed', Mockery::type('array'));
        $this->get('/pushbox?'.http_build_query(['class' => TypedNotification::class]))
            ->assertOk()->assertViewHas('previewError', null)->assertSee('id="pushbox-send-feedback"', false)
            ->assertSee('Device token rejected')->assertSee('Technical details');
    }

    public function test_provider_exception_restores_locale_and_cleans_capture(): void
    {
        [, , $client] = $this->prepare();
        $client->shouldReceive('sendMulticast')->once()->andThrow(new RuntimeException('Provider unavailable'));
        $this->post('/pushbox/send', $this->sendData('uk'))
            ->assertSessionHas('pushbox_send_result.ok', false)
            ->assertSessionHas('pushbox_send_result.note', 'Provider unavailable');
        self::assertSame('en', app()->getLocale());
    }

    public function test_notification_veto_is_skipped_and_never_calls_firebase(): void
    {
        [$book, $recipient, $client] = $this->prepare();
        $book->to($recipient)->add(fn (): VetoNotification => new VetoNotification('en'));
        $client->shouldNotReceive('sendMulticast');
        $report = app(SendFcmNotification::class)->send('test-device-token', VetoNotification::class, null, 'uk');
        self::assertFalse($report['ok']);
        self::assertSame('skipped', $report['status']);
        self::assertSame('en', app()->getLocale());
    }

    public function test_test_send_is_immediate_even_when_the_sync_queue_defers_until_commit(): void
    {
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'queue.connections.sync.after_commit' => true,
        ]);
        DB::purge('sqlite');
        Queue::fake();
        [, , $client] = $this->prepare();
        $sendCount = 0;
        $client->shouldReceive('sendMulticast')->once()->andReturnUsing(function () use (&$sendCount): MulticastSendReport {
            $sendCount++;

            return MulticastSendReport::withItems([
                SendReport::success(MessageTarget::with('token', 'test-device-token'), ['name' => 'projects/test/messages/immediate']),
            ]);
        });
        DB::beginTransaction();

        try {
            $this->post('/pushbox/send', $this->sendData())
                ->assertSessionHas('pushbox_send_result.ok', true)
                ->assertSessionHas('pushbox_send_result.status', 'accepted');

            self::assertSame(1, $sendCount);
            DB::commit();
            self::assertSame(1, $sendCount);
            Queue::assertNothingPushed();
        } finally {
            DB::rollBack(0);
        }
    }

    public function test_nested_test_sends_keep_their_own_results(): void
    {
        [, , $client] = $this->prepare();
        $client->shouldReceive('sendMulticast')->once()->withArgs(fn ($message, $tokens): bool => $tokens === ['test-device-token'])
            ->andReturn(MulticastSendReport::withItems([
                SendReport::success(MessageTarget::with('token', 'test-device-token'), ['name' => 'projects/test/messages/outer']),
            ]));
        $client->shouldReceive('sendMulticast')->once()->withArgs(fn ($message, $tokens): bool => $tokens === ['nested-device-token'])
            ->andReturn(MulticastSendReport::withItems([
                SendReport::failure(MessageTarget::with('token', 'nested-device-token'), new InvalidMessage('Nested send failed')),
            ]));
        $nestedReport = null;
        $nested = false;
        Event::listen(NotificationSent::class, function (NotificationSent $event) use (&$nested, &$nestedReport): void {
            if ($event->notification instanceof TestFcmNotification && ! $nested) {
                $nested = true;
                $nestedReport = app(SendFcmNotification::class)->send('nested-device-token', TypedNotification::class);
            }
        });

        $report = app(SendFcmNotification::class)->send('test-device-token', TypedNotification::class);

        self::assertTrue($report['ok']);
        self::assertStringContainsString('projects/test/messages/outer', $report['response_text']);
        self::assertFalse($nestedReport['ok']);
        self::assertSame('Nested send failed', $nestedReport['note']);
    }

    /** @return array<string, array{bool}> */
    public static function secondaryNotificationOutcomes(): array
    {
        return [
            'accepted secondary notification' => [false],
            'failed secondary notification' => [true],
        ];
    }

    #[DataProvider('secondaryNotificationOutcomes')]
    public function test_secondary_fcm_notifications_do_not_replace_the_test_send_result(bool $secondaryFails): void
    {
        [, $recipient, $client] = $this->prepare();
        $client->shouldReceive('sendMulticast')->once()->withArgs(fn ($message, $tokens): bool => $tokens === ['test-device-token'])
            ->andReturn(MulticastSendReport::withItems([
                SendReport::success(MessageTarget::with('token', 'test-device-token'), ['name' => 'projects/test/messages/actual-test-send']),
            ]));
        $secondaryTarget = MessageTarget::with('token', 'production-device');
        $secondaryReport = $secondaryFails
            ? SendReport::failure($secondaryTarget, new InvalidMessage('Secondary notification failed'))
            : SendReport::success($secondaryTarget, ['name' => 'projects/test/messages/secondary-notification']);
        $client->shouldReceive('sendMulticast')->once()->withArgs(fn ($message, $tokens): bool => $tokens === ['production-device'])
            ->andReturn(MulticastSendReport::withItems([$secondaryReport]));
        Event::listen(NotificationSent::class, function (NotificationSent $event) use ($recipient): void {
            if ($event->notification instanceof TestFcmNotification) {
                NotificationFacade::sendNow($recipient, new TypedNotification('en'), [FcmChannel::class]);
            }
        });

        $this->post('/pushbox/send', $this->sendData())
            ->assertSessionHas('pushbox_send_result.ok', true)
            ->assertSessionHas('pushbox_send_result.status', 'accepted');

        $response = session('pushbox_send_result.response_text');
        self::assertStringContainsString('projects/test/messages/actual-test-send', $response);
        self::assertStringNotContainsString('secondary-notification', $response);
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

class TypedNotification extends Notification implements ShouldQueue
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
