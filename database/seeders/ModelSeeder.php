<?php

namespace Database\Seeders;

use App\Models\AiModel;
use Illuminate\Database\Seeder;

class ModelSeeder extends Seeder
{
    public function run(): void
    {
        $models = [
            // ── Default / primary model ──────────────────────────────────────
            [
                'name'           => 'Qwen3 8B',
                'ollama_name'    => 'qwen3:8b',
                'enabled'        => true,
                'is_default'     => true,
                'context_length' => 32768,
            ],
            // ── Additional models (disabled until pulled in Ollama) ───────────
            [
                'name'           => 'Qwen3 14B',
                'ollama_name'    => 'qwen3:14b',
                'enabled'        => false,
                'is_default'     => false,
                'context_length' => 32768,
            ],
            [
                'name'           => 'Llama 3.2 3B',
                'ollama_name'    => 'llama3.2:3b',
                'enabled'        => false,
                'is_default'     => false,
                'context_length' => 128000,
            ],
            [
                'name'           => 'Llama 3.1 8B',
                'ollama_name'    => 'llama3.1:8b',
                'enabled'        => false,
                'is_default'     => false,
                'context_length' => 128000,
            ],
            [
                'name'           => 'Gemma 3 9B',
                'ollama_name'    => 'gemma3:9b',
                'enabled'        => false,
                'is_default'     => false,
                'context_length' => 8192,
            ],
            [
                'name'           => 'Mistral 7B',
                'ollama_name'    => 'mistral:7b',
                'enabled'        => false,
                'is_default'     => false,
                'context_length' => 32768,
            ],
            [
                'name'           => 'DeepSeek R1 8B',
                'ollama_name'    => 'deepseek-r1:8b',
                'enabled'        => false,
                'is_default'     => false,
                'context_length' => 65536,
            ],
            [
                'name'           => 'Phi-4 Mini',
                'ollama_name'    => 'phi4-mini:latest',
                'enabled'        => false,
                'is_default'     => false,
                'context_length' => 16384,
            ],
        ];

        foreach ($models as $model) {
            AiModel::updateOrCreate(
                ['ollama_name' => $model['ollama_name']],
                $model
            );
        }

        $this->command->info('AI models seeded: ' . count($models) . ' models (1 enabled, rest disabled until pulled)');
    }
}
