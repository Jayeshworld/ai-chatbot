<?php

namespace Tests\Mocks;

use App\Services\OllamaService;

class OllamaServiceMock extends OllamaService
{
    public function chat(array $messages, ?string $model = null, array $options = []): array
    {
        return [
            'content' => 'This is a mock AI response for testing.',
            'model' => $model ?? 'qwen3:8b',
            'prompt_tokens' => 50,
            'completion_tokens' => 20,
            'response_time_ms' => 100,
            'done' => true,
        ];
    }

    public function streamChat(array $messages, ?string $model = null, ?callable $onChunk = null, array $options = []): array
    {
        $content = 'This is a mock streaming response.';

        if ($onChunk) {
            foreach (str_split($content, 5) as $chunk) {
                $onChunk($chunk);
            }
        }

        return [
            'content' => $content,
            'model' => $model ?? 'qwen3:8b',
            'prompt_tokens' => 50,
            'completion_tokens' => 10,
        ];
    }

    public function listModels(): array
    {
        return [
            ['name' => 'qwen3:8b', 'size' => 5000000000],
        ];
    }

    public function isAvailable(): bool
    {
        return true;
    }
}
