<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function me(): JsonResponse
    {
        $user = auth()->user();

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
                'last_used_at' => $user->last_used_at?->toIso8601String(),
                'created_at' => $user->created_at->toIso8601String(),
            ],
            'error' => null,
        ]);
    }
}
