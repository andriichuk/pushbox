<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Tests\Feature;

use Andriichuk\Pushbox\Jobs\SendPushboxFcmNotificationJob;
use Andriichuk\Pushbox\Tests\Fixtures\ExampleNotification;
use Andriichuk\Pushbox\Tests\TestCase;
use Illuminate\Support\Facades\Bus;

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

    public function test_send_dispatches_sync_job_when_enabled(): void
    {
        $this->writeRouteFile();

        config(['pushbox.send.enabled' => true]);
        config(['pushbox.send_allow_non_local' => true]);

        Bus::fake();

        $this->from('/pushbox')
            ->post('/pushbox/send', [
                'class' => ExampleNotification::class,
                'fcm_token' => 'test-token-'.str_repeat('a', 20),
            ])
            ->assertRedirect();

        Bus::assertDispatched(SendPushboxFcmNotificationJob::class, function (SendPushboxFcmNotificationJob $job): bool {
            return $job->connection === 'sync';
        });
    }

    public function test_send_without_token_does_not_dispatch(): void
    {
        $this->writeRouteFile();

        config(['pushbox.send.enabled' => true]);
        config(['pushbox.send_allow_non_local' => true]);

        Bus::fake();

        $this->from('/pushbox')
            ->post('/pushbox/send', [
                'class' => ExampleNotification::class,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors(['pushbox']);

        Bus::assertNothingDispatched();
    }

    private function writeRouteFile(): void
    {
        $path = $this->app->basePath('routes/pushbox.php');
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
