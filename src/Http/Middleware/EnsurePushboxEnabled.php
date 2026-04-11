<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePushboxEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('pushbox.enabled', true)) {
            abort(404);
        }

        $allowed = config('pushbox.allowed_ips', []);
        if (is_array($allowed) && $allowed !== []) {
            $ip = $request->ip();
            if (! in_array($ip, $allowed, true)) {
                abort(403);
            }
        }

        return $next($request);
    }
}
