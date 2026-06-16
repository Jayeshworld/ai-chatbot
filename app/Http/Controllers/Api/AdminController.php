<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\RegistrationRequest;
use App\Models\User;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct(private AnalyticsService $analytics) {}

    public function metrics(): JsonResponse
    {
        return response()->json([
            'data' => $this->analytics->getDashboardMetrics(),
            'error' => null,
        ]);
    }

    public function users(): JsonResponse
    {
        $users = User::orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'data' => $users->items(),
            'meta' => [
                'page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
            ],
            'error' => null,
        ]);
    }

    public function createUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'is_admin' => 'sometimes|boolean',
        ]);

        $apiKey = User::generateApiKey();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'api_key' => $apiKey,
            'is_admin' => $validated['is_admin'] ?? false,
        ]);

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'api_key' => $apiKey,
                'is_admin' => (bool) $user->is_admin,
                'created_at' => $user->created_at->toIso8601String(),
            ],
            'error' => null,
        ], 201);
    }

    public function toggleUser(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $newState = !(bool) $user->is_active;
        $user->update(['is_active' => $newState]);

        return response()->json([
            'data' => ['id' => $user->id, 'is_active' => $newState],
            'error' => null,
        ]);
    }

    public function usage(Request $request): JsonResponse
    {
        $days = (int) $request->query('days', 30);

        return response()->json([
            'data' => [
                'by_day' => $this->analytics->getMessagesByDay($days),
                'by_model' => $this->analytics->getModelUsage(),
                'top_users' => $this->analytics->getTopUsers(),
            ],
            'error' => null,
        ]);
    }

    public function models(): JsonResponse
    {
        return response()->json([
            'data' => AiModel::all(),
            'error' => null,
        ]);
    }

    public function toggleModel(int $id): JsonResponse
    {
        $model = AiModel::findOrFail($id);
        $newState = !(bool) $model->enabled;
        $model->update(['enabled' => $newState]);

        return response()->json([
            'data' => ['id' => $model->id, 'enabled' => $newState],
            'error' => null,
        ]);
    }

    public function registrationRequests(Request $request): JsonResponse
    {
        $status = $request->query('status', 'pending');
        $query = RegistrationRequest::orderBy('created_at', 'desc');

        if (in_array($status, ['pending', 'approved', 'rejected'])) {
            $query->where('status', $status);
        }

        $requests = $query->paginate(20);

        return response()->json([
            'data' => $requests->items(),
            'meta' => [
                'page' => $requests->currentPage(),
                'total' => $requests->total(),
                'pending_count' => RegistrationRequest::where('status', 'pending')->count(),
            ],
            'error' => null,
        ]);
    }

    public function approveRequest(int $id): JsonResponse
    {
        $req = RegistrationRequest::findOrFail($id);

        if (!$req->isPending()) {
            return response()->json(['data' => null, 'error' => ['code' => 'ALREADY_REVIEWED', 'message' => 'This request has already been reviewed.']], 422);
        }

        if (User::where('email', $req->email)->exists()) {
            return response()->json(['data' => null, 'error' => ['code' => 'EMAIL_EXISTS', 'message' => 'A user with this email already exists.']], 422);
        }

        $apiKey = User::generateApiKey();

        User::create([
            'name' => $req->name,
            'email' => $req->email,
            'api_key' => $apiKey,
        ]);

        $req->update([
            'status' => 'approved',
            'api_key' => $apiKey,
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'data' => [
                'id' => $req->id,
                'name' => $req->name,
                'email' => $req->email,
                'api_key' => $apiKey,
                'status' => 'approved',
            ],
            'error' => null,
        ]);
    }

    public function rejectRequest(int $id): JsonResponse
    {
        $req = RegistrationRequest::findOrFail($id);

        if (!$req->isPending()) {
            return response()->json(['data' => null, 'error' => ['code' => 'ALREADY_REVIEWED', 'message' => 'This request has already been reviewed.']], 422);
        }

        $req->update(['status' => 'rejected', 'reviewed_at' => now()]);

        return response()->json([
            'data' => ['id' => $req->id, 'status' => 'rejected'],
            'error' => null,
        ]);
    }
}
