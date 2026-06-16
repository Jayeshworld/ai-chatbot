<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class HealthController extends Controller
{
    public function check(): JsonResponse
    {
        $ollamaUp = false;
        $modelLoaded = false;

        try {
            $response = Http::timeout(5)->get(config('ollama.base_url') . '/api/tags');
            $ollamaUp = $response->successful();

            if ($ollamaUp) {
                $models = collect($response->json('models', []));
                $default = config('ollama.default_model');
                $modelLoaded = $models->contains(
                    fn($m) => str_starts_with($m['name'], $default)
                );
            }
        } catch (\Exception) {
        }

        return response()->json([
            'data' => [
                'status' => $ollamaUp ? 'healthy' : 'degraded',
                'services' => [
                    'ollama' => $ollamaUp,
                    'model_loaded' => $modelLoaded,
                    'database' => $this->checkDatabase(),
                ],
                'timestamp' => now()->toIso8601String(),
            ],
            'error' => null,
        ]);
    }

    private function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception) {
            return false;
        }
    }
}
