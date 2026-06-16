<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuthentication
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-KEY');

        if (!$apiKey) {
            return response()->json([
                'data' => null,
                'error' => [
                    'code' => 'API_KEY_REQUIRED',
                    'message' => 'Provide X-API-KEY header.',
                ],
            ], 401);
        }

        $user = User::where('api_key', $apiKey)
                    ->where('is_active', true)
                    ->first();

        if (!$user) {
            return response()->json([
                'data' => null,
                'error' => [
                    'code' => 'INVALID_API_KEY',
                    'message' => 'Invalid or inactive API key.',
                ],
            ], 401);
        }

        $user->update(['last_used_at' => now()]);

        auth()->setUser($user);

        return $next($request);
    }
}
