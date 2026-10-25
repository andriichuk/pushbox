<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PushboxDeviceController extends Controller
{
    public const CACHE_KEY = 'pushbox.demo.device_token';

    public function __invoke(Request $request): JsonResponse
    {
        abort_unless(app()->environment(['local', 'testing']), 404);

        $key = config('pushbox_demo.registration_key');
        abort_unless(is_string($key) && $key !== '' && hash_equals($key, (string) $request->bearerToken()), 401);

        $validated = $request->validate([
            'token' => ['required', 'string', 'min:20', 'max:4096', 'regex:/\\A[A-Za-z0-9_:\-]+\\z/'],
        ]);

        Cache::store(config('pushbox_demo.cache_store'))->forever(self::CACHE_KEY, $validated['token']);

        return response()->json(['registered' => true]);
    }
}
