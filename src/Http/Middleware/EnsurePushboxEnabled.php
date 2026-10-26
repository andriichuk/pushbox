<?php

declare(strict_types=1);

namespace Andriichuk\Pushbox\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class EnsurePushboxEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('pushbox.enabled', true)) {
            abort(404);
        }

        if (Gate::has('viewPushbox') && ! Gate::allows('viewPushbox')) {
            abort(403);
        }

        $allowed = config('pushbox.allowed_ips', []);

        if (is_array($allowed) && $allowed !== []) {
            $ip = $request->ip();

            if (! in_array($ip, $allowed, true)) {
                abort(403);
            }
        }

        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
