<?php

namespace Database\Seeders;

use App\Models\AiModel;
use Illuminate\Database\Seeder;

class ModelSeeder extends Seeder
{
    public function run(): void
    {
        $models = [
            ['name' => 'Qwen3 8B', 'ollama_name' => 'qwen3:8b', 'enabled' => true, 'is_default' => true, 'context_length' => 32768],
            ['name' => 'Gemma3 9B', 'ollama_name' => 'gemma3:9b', 'enabled' => false, 'is_default' => false, 'context_length' => 8192],
            ['name' => 'Llama3.2 3B', 'ollama_name' => 'llama3.2:3b', 'enabled' => false, 'is_default' => false, 'context_length' => 128000],
        ];

        foreach ($models as $model) {
            AiModel::updateOrCreate(
                ['ollama_name' => $model['ollama_name']],
                $model
            );
        }
    }
}
