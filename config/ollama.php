<?php

return [
    'base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
    'default_model' => env('OLLAMA_DEFAULT_MODEL', 'qwen3:8b'),
    'timeout' => (int) env('OLLAMA_TIMEOUT', 120),
    'max_context_messages' => (int) env('OLLAMA_MAX_CONTEXT_MESSAGES', 20),
    'stream_chunk_size' => 1024,
    // Disable qwen3 extended "thinking" mode — significantly reduces latency
    'thinking_budget' => 0,
];
