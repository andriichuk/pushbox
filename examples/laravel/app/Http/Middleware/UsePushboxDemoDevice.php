<?php

namespace App\Http\Middleware;

use App\Http\Controllers\PushboxDeviceController;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class UsePushboxDemoDevice
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment(['local', 'testing']) && $request->routeIs('pushbox.*')) {
            $token = Cache::store(config('pushbox_demo.cache_store'))->get(PushboxDeviceController::CACHE_KEY);

            if (is_string($token) && $token !== '') {
                config(['pushbox.send.fcm.token' => $token]);
            }
        }

        return $next($request);
    }
}
