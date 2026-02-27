<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LocalhostOnly
{
    /** @var array<int, string> */
    private array $allowedIps = [
        '127.0.0.1',
        '::1',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('stan.security.localhost_only')) {
            return $next($request);
        }

        if (! in_array($request->ip(), $this->allowedIps, true)) {
            return response()->json(['error' => 'Access denied: localhost only'], 403);
        }

        return $next($request);
    }
}
