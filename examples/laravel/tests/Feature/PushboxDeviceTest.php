<?php

namespace Tests\Feature;

use Andriichuk\Pushbox\Http\Controllers\PushboxController;
use App\Http\Controllers\PushboxDeviceController;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PushboxDeviceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'pushbox_demo.registration_key' => 'demo-registration-key',
            'pushbox_demo.cache_store' => 'array',
        ]);
    }

    public function test_registered_device_becomes_the_pushbox_default_without_sending(): void
    {
        $token = str_repeat('device-token-', 12);

        $this->withToken('demo-registration-key')->postJson('/api/pushbox/device', ['token' => $token])
            ->assertOk()->assertExactJson(['registered' => true])->assertDontSee($token);

        $this->assertSame($token, Cache::store('array')->get(PushboxDeviceController::CACHE_KEY));

        $this->get(route('pushbox.index'))->assertOk()->assertViewHas('fcmSendTargetHash', hash('sha256', $token));
    }

    public function test_a_saved_browser_device_keeps_precedence_over_the_mobile_default(): void
    {
        Cache::store('array')->forever(PushboxDeviceController::CACHE_KEY, str_repeat('mobile-', 20));
        $savedToken = str_repeat('saved-', 20);

        $this->withSession([PushboxController::SESSION_FCM_DEVICE_TOKEN => $savedToken])
            ->get(route('pushbox.index'))->assertOk()->assertViewHas('fcmSendTargetHash', hash('sha256', $savedToken));
    }

    public function test_missing_and_incorrect_keys_cannot_replace_the_device(): void
    {
        foreach ([null, 'wrong-key'] as $key) {
            $headers = $key === null ? [] : ['Authorization' => 'Bearer '.$key];
            $this->postJson('/api/pushbox/device', ['token' => str_repeat('token-', 20)], $headers)->assertUnauthorized();
        }

        config(['pushbox_demo.registration_key' => null]);
        $this->withToken('demo-registration-key')->postJson('/api/pushbox/device', ['token' => str_repeat('token-', 20)])->assertUnauthorized();
        $this->assertNull(Cache::store('array')->get(PushboxDeviceController::CACHE_KEY));
    }

    public function test_invalid_tokens_are_rejected_and_do_not_replace_the_device(): void
    {
        foreach (['', 'short', str_repeat('x', 4097), 'a token containing whitespace', ['not-a-string']] as $token) {
            $this->withToken('demo-registration-key')->postJson('/api/pushbox/device', ['token' => $token])->assertUnprocessable();
        }

        $this->assertNull(Cache::store('array')->get(PushboxDeviceController::CACHE_KEY));
    }

    public function test_registration_is_unavailable_in_production(): void
    {
        $this->app->instance('env', 'production');
        $this->withToken('demo-registration-key')->postJson('/api/pushbox/device', ['token' => str_repeat('token-', 20)])->assertNotFound();
        $this->assertNull(Cache::store('array')->get(PushboxDeviceController::CACHE_KEY));
    }

    public function test_registration_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->withToken('demo-registration-key')->postJson('/api/pushbox/device', ['token' => str_repeat('token-', 20)])->assertOk();
        }

        $this->withToken('demo-registration-key')->postJson('/api/pushbox/device', ['token' => str_repeat('token-', 20)])->assertTooManyRequests();
    }
}
