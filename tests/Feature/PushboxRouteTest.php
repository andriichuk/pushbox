<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Tests\Feature;

use Andriichuk\Pushbox\Http\Controllers\PushboxController;
use Andriichuk\Pushbox\Sending\SendFcmNotification;
use Andriichuk\Pushbox\Tests\Fixtures\ExampleNotification;
use Andriichuk\Pushbox\Tests\TestCase;

class PushboxRouteTest extends TestCase
{
    public function test_index_renders_registered_notification(): void
    {
        $this->writeRouteFile();

        $this->get('/pushbox')
            ->assertOk()
            ->assertSee('ExampleNotification')
            ->assertSee('Hello');
    }

    public function test_disabled_returns_not_found(): void
    {
        $this->writeRouteFile();

        config(['pushbox.enabled' => false]);

        $this->get('/pushbox')->assertNotFound();
    }

    public function test_allowed_ips_restriction(): void
    {
        $this->writeRouteFile();

        config(['pushbox.allowed_ips' => ['203.0.113.10']]);

        $this->get('/pushbox')->assertForbidden();
    }

    public function test_device_token_is_saved_to_session_and_shown(): void
    {
        $this->writeRouteFile();

        $token = 'fcm-test-token-'.str_repeat('a', 40);

        $this->from('/pushbox')
            ->post('/pushbox/device-token', ['fcm_token' => $token])
            ->assertRedirect('/pushbox');

        $this->get('/pushbox')
            ->assertOk()
            ->assertSee($token, false);
    }

    public function test_send_calls_sender_and_returns_its_report(): void
    {
        $this->writeRouteFile();

        config(['pushbox.send.enabled' => true]);

        $token = 'test-token-'.str_repeat('a', 20);
        $report = ['ok' => true, 'status' => 'accepted', 'response_text' => 'Provider response'];
        $this->mock(SendFcmNotification::class)->shouldReceive('send')->once()
            ->with($token, ExampleNotification::class, null, null)->andReturn($report);
        $this->withSession([PushboxController::SESSION_FCM_DEVICE_TOKEN => $token])->from('/pushbox')
            ->post('/pushbox/send', [
                'class' => ExampleNotification::class,
                'target_hash' => hash('sha256', $token),
            ])
            ->assertRedirect();

        self::assertSame($report, session('pushbox_send_result'));
    }

    public function test_send_without_token_does_not_call_sender(): void
    {
        $this->writeRouteFile();

        config(['pushbox.send.enabled' => true]);

        $this->mock(SendFcmNotification::class)->shouldNotReceive('send');

        $this->from('/pushbox')
            ->post('/pushbox/send', [
                'class' => ExampleNotification::class,
                'target_hash' => hash('sha256', ''),
            ])
            ->assertRedirect()
            ->assertSessionHasErrors(['pushbox']);

    }

    private function writeRouteFile(): void
    {
        $path = config('pushbox.route_file');

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        $contents = <<<'PHP'
<?php

declare(strict_types=1);

use Andriichuk\Pushbox\Facades\Pushbox;
use Andriichuk\Pushbox\Tests\Fixtures\ExampleNotification;

Pushbox::add(ExampleNotification::class);

PHP;

        file_put_contents($path, $contents);
    }
}
