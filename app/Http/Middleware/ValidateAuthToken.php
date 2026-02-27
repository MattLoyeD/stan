<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateAuthToken
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->bearerToken()) {
            $user = \Laravel\Sanctum\PersonalAccessToken::findToken($request->bearerToken());

            if ($user) {
                $request->setUserResolver(fn () => $user->tokenable);

                return $next($request);
            }
        }

        return response()->json(['error' => 'Unauthorized'], 401);
    }
}
