<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => 'required|string|min:1|max:32000',
            'conversation_id' => 'sometimes|integer|exists:conversation_threads,id',
            'model' => 'sometimes|string|exists:ai_models,ollama_name',
            'system_prompt' => 'sometimes|string|max:4000',
        ];
    }
}
