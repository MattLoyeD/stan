<?php

namespace App\Http\Controllers;

use App\Services\Security\AuthTokenManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function user(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    public function setupStatus(): JsonResponse
    {
        $hasProvider = \App\Models\LlmProviderConfig::exists();

        return response()->json(['has_provider' => $hasProvider]);
    }

    public function autoToken(AuthTokenManager $tokenManager): JsonResponse
    {
        $token = $tokenManager->getToken();

        if (! $token) {
            return response()->json(['error' => 'No token available'], 404);
        }

        return response()->json(['token' => $token]);
    }
}
