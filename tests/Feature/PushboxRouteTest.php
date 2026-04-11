<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Tests\Feature;

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
