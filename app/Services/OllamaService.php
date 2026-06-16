<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaService
{
    private string $baseUrl;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('ollama.base_url');
        $this->timeout = config('ollama.timeout');
        set_time_limit(0); // Ollama calls can exceed PHP default 30s
    }

    /**
     * Non-streaming chat completion.
     * Returns assistant content + token counts + response time.
     */
    public function chat(array $messages, ?string $model = null, array $options = []): array
    {
        $model = $model ?? config('ollama.default_model');
        $startTime = microtime(true);

        try {
            $payload = [
                'model' => $model,
                'messages' => $messages,
                'stream' => false,
                'options' => array_merge([
                    'temperature' => 0.7,
                    'top_p' => 0.9,
                ], $options),
            ];

            $thinkingBudget = config('ollama.thinking_budget', 0);
            if ($thinkingBudget === 0) {
                $payload['think'] = false;
            }

            $response = Http::timeout($this->timeout)->post("{$this->baseUrl}/api/chat", $payload);

            if (!$response->successful()) {
                throw new \RuntimeException('Ollama error: ' . $response->body());
            }

            $data = $response->json();
            $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            return [
                'content' => $data['message']['content'] ?? '',
                'model' => $data['model'] ?? $model,
                'prompt_tokens' => $data['prompt_eval_count'] ?? 0,
                'completion_tokens' => $data['eval_count'] ?? 0,
                'response_time_ms' => $responseTimeMs,
                'done' => $data['done'] ?? true,
            ];
        } catch (\Exception $e) {
            Log::error('OllamaService::chat error', [
                'model' => $model,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Streaming chat — calls $onChunk with each text token.
     * Uses PHP native fopen stream for reliable real-time NDJSON reading.
     */
    public function streamChat(
        array $messages,
        ?string $model = null,
        ?callable $onChunk = null,
        array $options = []
    ): array {
        $model = $model ?? config('ollama.default_model');
        $fullContent = '';
        $promptTokens = 0;
        $completionTokens = 0;

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'stream' => true,
            'options' => $options ?: (object)[], // must be JSON object {}, not array []
        ];

        if (config('ollama.thinking_budget', 0) === 0) {
            $payload['think'] = false;
        }

        // Spawn curl as subprocess — use absolute path (web context has a restricted PATH).
        // Pipe stdout; explicit /dev/null stdin prevents inheriting PHP built-in server's stdin.
        $curlBin = '/usr/bin/curl';
        $curlCmd = sprintf(
            '%s -s -X POST %s/api/chat -H "Content-Type: application/json" --data-binary %s --no-buffer',
            $curlBin,
            escapeshellarg($this->baseUrl),
            escapeshellarg(json_encode($payload))
        );

        $proc = proc_open($curlCmd, [
            0 => ['file', '/dev/null', 'r'], // prevent stdin inheritance from PHP built-in server
            1 => ['pipe', 'w'],              // stdout → our read pipe
            2 => ['pipe', 'w'],              // stderr → capture for error logging
        ], $pipes);

        if (!is_resource($proc)) {
            throw new \RuntimeException('Failed to start curl stream process');
        }

        stream_set_blocking($pipes[1], true); // blocking reads — wait for data
        stream_set_blocking($pipes[2], false); // non-blocking stderr

        while (($line = fgets($pipes[1])) !== false) {
            $line = trim($line);
            if ($line === '') continue;

            $chunk = json_decode($line, true);
            if (!is_array($chunk)) continue;

            $token = $chunk['message']['content'] ?? '';
            $fullContent .= $token;

            if ($onChunk && $token !== '') {
                $onChunk($token);
            }

            if ($chunk['done'] ?? false) {
                $promptTokens = $chunk['prompt_eval_count'] ?? 0;
                $completionTokens = $chunk['eval_count'] ?? 0;
                break;
            }
        }

        $stderrOutput = stream_get_contents($pipes[2]);
        if ($stderrOutput) {
            Log::warning('OllamaService streamChat stderr: ' . $stderrOutput);
        }
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($proc);

        return [
            'content' => $fullContent,
            'model' => $model,
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
        ];
    }

    public function listModels(): array
    {
        $response = Http::timeout(10)->get("{$this->baseUrl}/api/tags");
        return $response->json('models', []);
    }

    public function isAvailable(): bool
    {
        try {
            return Http::timeout(5)->get("{$this->baseUrl}/api/tags")->successful();
        } catch (\Exception) {
            return false;
        }
    }
}
