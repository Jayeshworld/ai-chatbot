<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function success(mixed $data, int $status = 200, array $meta = []): JsonResponse
    {
        $body = ['data' => $data, 'error' => null];

        if (!empty($meta)) {
            $body['meta'] = $meta;
        }

        return response()->json($body, $status);
    }

    protected function paginated(mixed $paginator): JsonResponse
    {
        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
            'error' => null,
        ]);
    }

    protected function error(string $code, string $message, int $status = 400): JsonResponse
    {
        return response()->json([
            'data' => null,
            'error' => ['code' => $code, 'message' => $message],
        ], $status);
    }
}
