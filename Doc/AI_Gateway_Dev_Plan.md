# AI Gateway Platform v1 — Detailed Development Plan
### Laravel 12 + MySQL + Ollama (Qwen3:8B) | Self-Hosted ChatGPT Alternative

---

## Overview

This plan converts the Technical Specification into a concrete, step-by-step build guide. Each phase is independently deployable. Follow phases in order — each one builds on the last.

**Stack:**
- Backend: Laravel 12 (PHP 8.3+)
- Database: MySQL 8.x
- AI Engine: Ollama (running locally on your server)
- Model: Qwen3:8B (primary), multi-model ready
- Cache/Queue: Redis
- Web Server: Nginx + PHP-FPM
- OS: Ubuntu 24.04

---

## Pre-Development Setup

### Server Requirements
```
CPU: 8+ cores recommended (Qwen3:8B needs headroom)
RAM: 16GB minimum, 32GB recommended
Disk: 50GB+ (model files are large)
OS: Ubuntu 24.04 LTS
```

### Verify Ollama is Running
```bash
# Check Ollama service
curl http://localhost:11434/api/tags

# Should return list of models. If Qwen3:8B is not pulled:
ollama pull qwen3:8b

# Test a quick generation
curl http://localhost:11434/api/generate -d '{
  "model": "qwen3:8b",
  "prompt": "Hello",
  "stream": false
}'
```

### Install System Dependencies
```bash
# PHP 8.3 + extensions
sudo apt install php8.3-fpm php8.3-mysql php8.3-redis php8.3-curl \
  php8.3-xml php8.3-mbstring php8.3-zip

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Node.js (for any frontend tooling)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo bash -
sudo apt install nodejs

# Redis
sudo apt install redis-server

# MySQL 8
sudo apt install mysql-server-8.0
```

---

## Phase 1 — Project Foundation & Authentication

**Goal:** Bare Laravel project with API key auth, user management, and health check.

**Estimated Time:** 1–2 days

### Step 1.1 — Create Laravel Project

```bash
composer create-project laravel/laravel ai-gateway
cd ai-gateway

# Install required packages
composer require laravel/sanctum
composer require guzzlehttp/guzzle        # For Ollama HTTP calls
composer require spatie/laravel-rate-limiting  # Rate limiting helpers
```

### Step 1.2 — Environment Configuration

```ini
# .env (critical settings)
APP_NAME="AI Gateway"
APP_ENV=production
APP_URL=https://ai.company.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ai_gateway
DB_USERNAME=ai_gateway_user
DB_PASSWORD=your_strong_password

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

OLLAMA_BASE_URL=http://localhost:11434
OLLAMA_DEFAULT_MODEL=qwen3:8b
OLLAMA_TIMEOUT=120
OLLAMA_MAX_CONTEXT_MESSAGES=20

RATE_LIMIT_PER_MINUTE=60
```

### Step 1.3 — Database Migrations

Create all migrations in this order:

**Migration 1: users table**
```php
// database/migrations/xxxx_create_users_table.php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->string('api_key', 64)->unique();
    $table->boolean('is_active')->default(true);
    $table->json('permissions')->nullable(); // future: granular permissions
    $table->timestamp('last_used_at')->nullable();
    $table->timestamps();
    
    $table->index('api_key');
    $table->index('is_active');
});
```

**Migration 2: models table**
```php
Schema::create('models', function (Blueprint $table) {
    $table->id();
    $table->string('name');           // Display: "Qwen3 8B"
    $table->string('ollama_name');    // API: "qwen3:8b"
    $table->boolean('enabled')->default(true);
    $table->boolean('is_default')->default(false);
    $table->integer('context_length')->default(8192);
    $table->timestamps();
});
```

**Migration 3: conversation_threads table**
```php
Schema::create('conversation_threads', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
    $table->string('title')->nullable();
    $table->text('system_prompt')->nullable();
    $table->string('model')->default('qwen3:8b');
    $table->boolean('is_active')->default(true);
    $table->integer('message_count')->default(0);
    $table->timestamps();
    
    $table->index(['user_id', 'created_at']);
});
```

**Migration 4: conversation_messages table**
```php
Schema::create('conversation_messages', function (Blueprint $table) {
    $table->id();
    $table->foreignId('conversation_id')
          ->constrained('conversation_threads')
          ->onDelete('cascade');
    $table->enum('role', ['system', 'user', 'assistant']);
    $table->longText('content');
    $table->integer('token_count')->default(0);
    $table->json('metadata')->nullable(); // future: tool calls, citations
    $table->timestamps();
    
    $table->index(['conversation_id', 'created_at']);
    $table->index('role');
});
```

**Migration 5: chat_usage table**
```php
Schema::create('chat_usage', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users');
    $table->foreignId('conversation_id')->constrained('conversation_threads');
    $table->string('model');
    $table->integer('prompt_tokens')->default(0);
    $table->integer('completion_tokens')->default(0);
    $table->integer('response_time_ms')->default(0);
    $table->timestamps();
    
    $table->index(['user_id', 'created_at']);
});
```

Run migrations:
```bash
php artisan migrate
```

### Step 1.4 — Eloquent Models

```php
// app/Models/User.php
class User extends Model {
    protected $fillable = ['name', 'email', 'api_key', 'is_active'];
    protected $hidden = ['api_key'];
    
    public function conversations() {
        return $this->hasMany(ConversationThread::class);
    }
    
    public static function generateApiKey(): string {
        return 'ak_' . bin2hex(random_bytes(32));
    }
}

// app/Models/ConversationThread.php
class ConversationThread extends Model {
    protected $fillable = ['user_id', 'title', 'system_prompt', 'model'];
    
    public function messages() {
        return $this->hasMany(ConversationMessage::class, 'conversation_id')
                    ->orderBy('created_at', 'asc');
    }
    
    public function recentMessages(int $limit = 20) {
        return $this->hasMany(ConversationMessage::class, 'conversation_id')
                    ->latest()
                    ->limit($limit)
                    ->get()
                    ->reverse()
                    ->values();
    }
    
    public function user() {
        return $this->belongsTo(User::class);
    }
}

// app/Models/ConversationMessage.php
class ConversationMessage extends Model {
    protected $fillable = ['conversation_id', 'role', 'content', 'token_count'];
}
```

### Step 1.5 — API Key Middleware

```php
// app/Http/Middleware/ApiKeyAuthentication.php
namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class ApiKeyAuthentication {
    public function handle(Request $request, Closure $next) {
        $apiKey = $request->header('X-API-KEY');
        
        if (!$apiKey) {
            return response()->json([
                'error' => 'API key required',
                'message' => 'Provide X-API-KEY header'
            ], 401);
        }
        
        $user = User::where('api_key', $apiKey)
                    ->where('is_active', true)
                    ->first();
        
        if (!$user) {
            return response()->json([
                'error' => 'Invalid or inactive API key'
            ], 401);
        }
        
        // Update last used
        $user->update(['last_used_at' => now()]);
        
        // Attach user to request
        $request->merge(['auth_user' => $user]);
        auth()->setUser($user);
        
        return $next($request);
    }
}
```

### Step 1.6 — Register Middleware & Routes

```php
// bootstrap/app.php (Laravel 12 style)
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'api.auth' => \App\Http\Middleware\ApiKeyAuthentication::class,
    ]);
})
```

```php
// routes/api.php
Route::prefix('v1')->group(function () {
    
    // Public routes
    Route::get('/health', [HealthController::class, 'check']);
    
    // Authenticated routes
    Route::middleware(['api.auth', 'throttle:60,1'])->group(function () {
        
        // Auth info
        Route::get('/auth/me', [AuthController::class, 'me']);
        
        // Conversations
        Route::get('/conversations', [ConversationController::class, 'index']);
        Route::post('/conversations', [ConversationController::class, 'store']);
        Route::get('/conversations/{id}', [ConversationController::class, 'show']);
        Route::put('/conversations/{id}', [ConversationController::class, 'update']);
        Route::delete('/conversations/{id}', [ConversationController::class, 'destroy']);
        
        // Messages
        Route::get('/conversations/{id}/messages', [MessageController::class, 'index']);
        
        // Chat (core endpoint)
        Route::post('/chat/send', [ChatController::class, 'send']);
        Route::post('/chat/stream', [ChatController::class, 'stream']);
        
        // Models
        Route::get('/models', [ModelController::class, 'index']);
        Route::post('/models/change', [ModelController::class, 'change']);
    });
});
```

### Step 1.7 — Health Check Controller

```php
// app/Http/Controllers/Api/HealthController.php
public function check() {
    $ollamaStatus = false;
    $modelStatus = false;
    
    try {
        $response = Http::timeout(5)->get(config('ollama.base_url') . '/api/tags');
        $ollamaStatus = $response->successful();
        
        if ($ollamaStatus) {
            $models = collect($response->json('models', []));
            $modelStatus = $models->contains(fn($m) => 
                str_starts_with($m['name'], config('ollama.default_model'))
            );
        }
    } catch (\Exception $e) {
        // Ollama unreachable
    }
    
    return response()->json([
        'status' => $ollamaStatus ? 'healthy' : 'degraded',
        'services' => [
            'ollama' => $ollamaStatus,
            'model_loaded' => $modelStatus,
            'database' => $this->checkDatabase(),
        ],
        'timestamp' => now()->toIso8601String(),
    ]);
}
```

**Phase 1 Deliverable:** Working API with key authentication, user lookup, and health endpoint. Test with:
```bash
curl -H "X-API-KEY: ak_your_key" https://ai.company.com/api/v1/auth/me
curl https://ai.company.com/api/v1/health
```

---

## Phase 2 — Ollama Service Layer

**Goal:** A robust service class that talks to Ollama — handles generation, chat format, errors, and model switching.

**Estimated Time:** 1 day

### Step 2.1 — Config File

```php
// config/ollama.php
return [
    'base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
    'default_model' => env('OLLAMA_DEFAULT_MODEL', 'qwen3:8b'),
    'timeout' => env('OLLAMA_TIMEOUT', 120),
    'max_context_messages' => env('OLLAMA_MAX_CONTEXT_MESSAGES', 20),
    'stream_chunk_size' => 1024,
];
```

### Step 2.2 — OllamaService

```php
// app/Services/OllamaService.php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaService {
    
    private string $baseUrl;
    private int $timeout;
    
    public function __construct() {
        $this->baseUrl = config('ollama.base_url');
        $this->timeout = config('ollama.timeout');
    }
    
    /**
     * Send a chat completion request (non-streaming).
     * Returns the assistant's message text.
     */
    public function chat(array $messages, string $model = null, array $options = []): array {
        $model = $model ?? config('ollama.default_model');
        
        $startTime = microtime(true);
        
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/api/chat", [
                    'model' => $model,
                    'messages' => $messages,
                    'stream' => false,
                    'options' => array_merge([
                        'temperature' => 0.7,
                        'top_p' => 0.9,
                    ], $options),
                ]);
            
            if (!$response->successful()) {
                throw new \Exception("Ollama error: " . $response->body());
            }
            
            $data = $response->json();
            $responseTimeMs = (int)((microtime(true) - $startTime) * 1000);
            
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
     * Stream chat response — yields chunks as they arrive.
     * Callback receives each text chunk string.
     */
    public function streamChat(
        array $messages, 
        string $model = null, 
        callable $onChunk = null,
        array $options = []
    ): array {
        $model = $model ?? config('ollama.default_model');
        $fullContent = '';
        $promptTokens = 0;
        $completionTokens = 0;
        
        $response = Http::timeout($this->timeout)
            ->withOptions(['stream' => true])
            ->post("{$this->baseUrl}/api/chat", [
                'model' => $model,
                'messages' => $messages,
                'stream' => true,
                'options' => $options,
            ]);
        
        $body = $response->toPsrResponse()->getBody();
        
        while (!$body->eof()) {
            $line = '';
            while (!$body->eof()) {
                $char = $body->read(1);
                if ($char === "\n") break;
                $line .= $char;
            }
            
            $line = trim($line);
            if (empty($line)) continue;
            
            $chunk = json_decode($line, true);
            if (!$chunk) continue;
            
            $token = $chunk['message']['content'] ?? '';
            $fullContent .= $token;
            
            if ($onChunk && $token) {
                $onChunk($token);
            }
            
            if ($chunk['done'] ?? false) {
                $promptTokens = $chunk['prompt_eval_count'] ?? 0;
                $completionTokens = $chunk['eval_count'] ?? 0;
                break;
            }
        }
        
        return [
            'content' => $fullContent,
            'model' => $model,
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
        ];
    }
    
    /**
     * List available models from Ollama.
     */
    public function listModels(): array {
        $response = Http::timeout(10)->get("{$this->baseUrl}/api/tags");
        return $response->json('models', []);
    }
    
    /**
     * Check if Ollama is reachable.
     */
    public function isAvailable(): bool {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/api/tags");
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}
```

### Step 2.3 — Register as Service Provider

```php
// app/Providers/AppServiceProvider.php
public function register(): void {
    $this->app->singleton(\App\Services\OllamaService::class);
}
```

---

## Phase 3 — Conversation Engine & Context Management

**Goal:** The brain of the system. Loads history, builds prompt with correct context window, stores messages, handles token estimation.

**Estimated Time:** 1–2 days

### Step 3.1 — ConversationService

```php
// app/Services/ConversationService.php
namespace App\Services;

use App\Models\ConversationThread;
use App\Models\ConversationMessage;
use App\Models\ChatUsage;

class ConversationService {
    
    private int $maxContextMessages;
    
    public function __construct() {
        $this->maxContextMessages = config('ollama.max_context_messages', 20);
    }
    
    /**
     * Build the full message array to send to Ollama.
     * Includes system prompt + last N messages + new user message.
     */
    public function buildContext(ConversationThread $thread, string $newUserMessage): array {
        $messages = [];
        
        // 1. System prompt (always first)
        $systemPrompt = $thread->system_prompt 
            ?? "You are a helpful AI assistant. Be clear, accurate, and concise.";
        
        $messages[] = [
            'role' => 'system',
            'content' => $systemPrompt,
        ];
        
        // 2. Load last N messages from history
        $history = ConversationMessage::where('conversation_id', $thread->id)
            ->where('role', '!=', 'system')
            ->orderBy('created_at', 'desc')
            ->limit($this->maxContextMessages)
            ->get()
            ->reverse()
            ->values();
        
        foreach ($history as $msg) {
            $messages[] = [
                'role' => $msg->role,
                'content' => $msg->content,
            ];
        }
        
        // 3. New user message (current turn)
        $messages[] = [
            'role' => 'user',
            'content' => $newUserMessage,
        ];
        
        return $messages;
    }
    
    /**
     * Save the user message to DB.
     */
    public function saveUserMessage(ConversationThread $thread, string $content): ConversationMessage {
        $message = ConversationMessage::create([
            'conversation_id' => $thread->id,
            'role' => 'user',
            'content' => $content,
            'token_count' => $this->estimateTokens($content),
        ]);
        
        $thread->increment('message_count');
        
        return $message;
    }
    
    /**
     * Save assistant response to DB.
     */
    public function saveAssistantMessage(
        ConversationThread $thread, 
        string $content, 
        int $tokenCount = 0
    ): ConversationMessage {
        $message = ConversationMessage::create([
            'conversation_id' => $thread->id,
            'role' => 'assistant',
            'content' => $content,
            'token_count' => $tokenCount ?: $this->estimateTokens($content),
        ]);
        
        $thread->increment('message_count');
        
        // Auto-generate title from first exchange if not set
        if (!$thread->title && $thread->message_count <= 2) {
            $this->generateTitle($thread);
        }
        
        return $message;
    }
    
    /**
     * Log usage metrics.
     */
    public function logUsage(
        int $userId,
        int $conversationId,
        string $model,
        int $promptTokens,
        int $completionTokens,
        int $responseTimeMs
    ): void {
        ChatUsage::create([
            'user_id' => $userId,
            'conversation_id' => $conversationId,
            'model' => $model,
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'response_time_ms' => $responseTimeMs,
        ]);
    }
    
    /**
     * Simple token estimator: ~4 chars per token (rough approximation).
     */
    public function estimateTokens(string $text): int {
        return (int)ceil(strlen($text) / 4);
    }
    
    /**
     * Auto-generate conversation title using first user message.
     */
    private function generateTitle(ConversationThread $thread): void {
        $firstMessage = ConversationMessage::where('conversation_id', $thread->id)
            ->where('role', 'user')
            ->first();
        
        if ($firstMessage) {
            $title = substr($firstMessage->content, 0, 60);
            if (strlen($firstMessage->content) > 60) $title .= '...';
            $thread->update(['title' => $title]);
        }
    }
    
    /**
     * Get conversation with ownership check.
     */
    public function getConversationForUser(int $conversationId, int $userId): ?ConversationThread {
        return ConversationThread::where('id', $conversationId)
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->first();
    }
}
```

---

## Phase 4 — Chat API Endpoints

**Goal:** The actual chat controllers — both blocking and streaming.

**Estimated Time:** 1 day

### Step 4.1 — ChatController (Non-Streaming)

```php
// app/Http/Controllers/Api/ChatController.php
namespace App\Http\Controllers\Api;

use App\Services\ConversationService;
use App\Services\OllamaService;
use App\Http\Requests\SendMessageRequest;
use Illuminate\Http\JsonResponse;

class ChatController extends Controller {
    
    public function __construct(
        private ConversationService $conversationService,
        private OllamaService $ollamaService
    ) {}
    
    /**
     * POST /api/v1/chat/send
     * Standard (non-streaming) chat response.
     */
    public function send(SendMessageRequest $request): JsonResponse {
        $user = auth()->user();
        
        // 1. Load or create conversation
        $thread = $this->resolveThread($request, $user);
        
        // 2. Build context (history + new message)
        $messages = $this->conversationService->buildContext(
            $thread, 
            $request->input('message')
        );
        
        // 3. Save user message
        $this->conversationService->saveUserMessage($thread, $request->input('message'));
        
        // 4. Send to Ollama
        try {
            $result = $this->ollamaService->chat($messages, $thread->model);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'AI service unavailable',
                'detail' => $e->getMessage()
            ], 503);
        }
        
        // 5. Save assistant response
        $assistantMessage = $this->conversationService->saveAssistantMessage(
            $thread,
            $result['content'],
            $result['completion_tokens']
        );
        
        // 6. Log usage
        $this->conversationService->logUsage(
            $user->id,
            $thread->id,
            $result['model'],
            $result['prompt_tokens'],
            $result['completion_tokens'],
            $result['response_time_ms']
        );
        
        return response()->json([
            'conversation_id' => $thread->id,
            'message' => [
                'id' => $assistantMessage->id,
                'role' => 'assistant',
                'content' => $result['content'],
                'created_at' => $assistantMessage->created_at,
            ],
            'usage' => [
                'prompt_tokens' => $result['prompt_tokens'],
                'completion_tokens' => $result['completion_tokens'],
                'response_time_ms' => $result['response_time_ms'],
            ],
        ]);
    }
    
    /**
     * POST /api/v1/chat/stream
     * Server-Sent Events streaming response.
     */
    public function stream(SendMessageRequest $request) {
        $user = auth()->user();
        $thread = $this->resolveThread($request, $user);
        $messages = $this->conversationService->buildContext($thread, $request->input('message'));
        
        $this->conversationService->saveUserMessage($thread, $request->input('message'));
        
        $fullResponse = '';
        $usageData = [];
        
        return response()->stream(function () use (
            $thread, $messages, $user, &$fullResponse, &$usageData
        ) {
            // Send conversation ID first
            echo "data: " . json_encode([
                'type' => 'start',
                'conversation_id' => $thread->id,
            ]) . "\n\n";
            ob_flush(); flush();
            
            // Stream tokens from Ollama
            $result = $this->ollamaService->streamChat(
                $messages,
                $thread->model,
                function (string $chunk) use (&$fullResponse) {
                    $fullResponse .= $chunk;
                    echo "data: " . json_encode([
                        'type' => 'chunk',
                        'content' => $chunk,
                    ]) . "\n\n";
                    ob_flush(); flush();
                }
            );
            
            // Save complete response
            $assistantMessage = $this->conversationService->saveAssistantMessage(
                $thread,
                $result['content'],
                $result['completion_tokens']
            );
            
            $this->conversationService->logUsage(
                $user->id, $thread->id, $result['model'],
                $result['prompt_tokens'], $result['completion_tokens'], 0
            );
            
            // Send completion event
            echo "data: " . json_encode([
                'type' => 'done',
                'message_id' => $assistantMessage->id,
                'usage' => [
                    'prompt_tokens' => $result['prompt_tokens'],
                    'completion_tokens' => $result['completion_tokens'],
                ],
            ]) . "\n\n";
            ob_flush(); flush();
            
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no', // critical for Nginx
            'Connection' => 'keep-alive',
        ]);
    }
    
    /**
     * Resolve conversation: use existing or create new.
     */
    private function resolveThread($request, $user): \App\Models\ConversationThread {
        if ($request->has('conversation_id')) {
            $thread = $this->conversationService->getConversationForUser(
                $request->input('conversation_id'),
                $user->id
            );
            
            if (!$thread) {
                abort(404, 'Conversation not found');
            }
            
            return $thread;
        }
        
        // Create new conversation
        return \App\Models\ConversationThread::create([
            'user_id' => $user->id,
            'model' => $request->input('model', config('ollama.default_model')),
            'system_prompt' => $request->input('system_prompt'),
        ]);
    }
}
```

### Step 4.2 — Request Validation

```php
// app/Http/Requests/SendMessageRequest.php
class SendMessageRequest extends FormRequest {
    public function rules(): array {
        return [
            'message' => 'required|string|min:1|max:32000',
            'conversation_id' => 'sometimes|integer|exists:conversation_threads,id',
            'model' => 'sometimes|string|exists:models,ollama_name',
            'system_prompt' => 'sometimes|string|max:4000',
        ];
    }
}
```

### Step 4.3 — Conversation Controller

```php
// app/Http/Controllers/Api/ConversationController.php
public function index(Request $request): JsonResponse {
    $user = auth()->user();
    
    $conversations = ConversationThread::where('user_id', $user->id)
        ->where('is_active', true)
        ->orderBy('updated_at', 'desc')
        ->paginate(20);
    
    return response()->json($conversations);
}

public function store(Request $request): JsonResponse {
    $user = auth()->user();
    
    $thread = ConversationThread::create([
        'user_id' => $user->id,
        'title' => $request->input('title'),
        'system_prompt' => $request->input('system_prompt'),
        'model' => $request->input('model', config('ollama.default_model')),
    ]);
    
    return response()->json($thread, 201);
}

public function show(int $id): JsonResponse {
    $user = auth()->user();
    $thread = ConversationThread::where('id', $id)
        ->where('user_id', $user->id)
        ->firstOrFail();
    
    return response()->json($thread->load('messages'));
}

public function destroy(int $id): JsonResponse {
    $user = auth()->user();
    ConversationThread::where('id', $id)
        ->where('user_id', $user->id)
        ->update(['is_active' => false]);
    
    return response()->json(['message' => 'Conversation deleted']);
}
```

---

## Phase 5 — Nginx & Production Deployment

**Goal:** Get it running securely on Ubuntu 24.04 with SSL.

**Estimated Time:** Half a day

### Step 5.1 — Nginx Configuration

```nginx
# /etc/nginx/sites-available/ai-gateway
server {
    listen 80;
    server_name ai.company.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name ai.company.com;
    root /var/www/ai-gateway/public;
    index index.php;
    
    ssl_certificate /etc/letsencrypt/live/ai.company.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/ai.company.com/privkey.pem;
    
    # Critical for SSE streaming
    location /api/v1/chat/stream {
        proxy_buffering off;
        proxy_cache off;
        fastcgi_buffering off;
        
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_read_timeout 300;
    }
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_read_timeout 120;
    }
    
    # Block direct access to Ollama port via Nginx
    location /ollama/ {
        deny all;
        return 403;
    }
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";
}
```

### Step 5.2 — Firewall Rules

```bash
# Allow web traffic
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 22/tcp

# Block external access to Ollama (internal only)
sudo ufw deny 11434/tcp

sudo ufw enable
```

### Step 5.3 — Ollama — Bind to Localhost Only

```bash
# Edit Ollama systemd service
sudo systemctl edit ollama

# Add this:
[Service]
Environment="OLLAMA_HOST=127.0.0.1:11434"

sudo systemctl restart ollama
```

### Step 5.4 — Laravel Production Config

```bash
cd /var/www/ai-gateway

# Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Set permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Seed default model
php artisan db:seed --class=ModelSeeder
```

```php
// database/seeders/ModelSeeder.php
Model::insert([
    ['name' => 'Qwen3 8B', 'ollama_name' => 'qwen3:8b', 'enabled' => true, 'is_default' => true],
    ['name' => 'Gemma3', 'ollama_name' => 'gemma3:9b', 'enabled' => false, 'is_default' => false],
]);
```

---

## Phase 6 — Admin Dashboard & Analytics

**Goal:** Usage metrics, user management, model control.

**Estimated Time:** 2–3 days

### Step 6.1 — Analytics Queries

```php
// app/Services/AnalyticsService.php

public function getDashboardMetrics(): array {
    return [
        'total_users' => User::where('is_active', true)->count(),
        'total_conversations' => ConversationThread::count(),
        'total_messages' => ConversationMessage::count(),
        'messages_today' => ConversationMessage::whereDate('created_at', today())->count(),
        'avg_response_time_ms' => (int) ChatUsage::avg('response_time_ms'),
        'total_tokens_used' => ChatUsage::sum('prompt_tokens') + ChatUsage::sum('completion_tokens'),
        'active_users_7d' => ChatUsage::where('created_at', '>=', now()->subDays(7))
            ->distinct('user_id')->count(),
    ];
}

public function getMessagesByDay(int $days = 30): array {
    return ConversationMessage::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
        ->where('created_at', '>=', now()->subDays($days))
        ->groupBy('date')
        ->orderBy('date')
        ->get()
        ->toArray();
}

public function getModelUsage(): array {
    return ChatUsage::select('model', DB::raw('COUNT(*) as requests'))
        ->groupBy('model')
        ->get()
        ->toArray();
}
```

### Step 6.2 — Admin Routes & Middleware

```php
// Add admin flag to users table
$table->boolean('is_admin')->default(false);

// Admin middleware
class AdminMiddleware {
    public function handle(Request $request, Closure $next) {
        if (!auth()->user()->is_admin) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        return $next($request);
    }
}

// Admin routes
Route::middleware(['api.auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/metrics', [AdminController::class, 'metrics']);
    Route::get('/users', [AdminController::class, 'users']);
    Route::post('/users', [AdminController::class, 'createUser']);
    Route::put('/users/{id}/toggle', [AdminController::class, 'toggleUser']);
    Route::get('/usage', [AdminController::class, 'usage']);
    Route::get('/models', [AdminController::class, 'models']);
    Route::post('/models/{id}/toggle', [AdminController::class, 'toggleModel']);
});
```

---

## Phase 7 — Testing & Documentation

**Goal:** Unit + feature tests, Postman collection, OpenAPI spec.

**Estimated Time:** 2 days

### Step 7.1 — Key Tests to Write

```php
// tests/Feature/ChatTest.php

// Test: Send message creates conversation and returns response
public function test_send_message_without_conversation_id_creates_new_thread() {
    $user = User::factory()->create();
    
    $response = $this->withHeader('X-API-KEY', $user->api_key)
        ->postJson('/api/v1/chat/send', [
            'message' => 'Hello, what is Laravel?'
        ]);
    
    $response->assertStatus(200)
        ->assertJsonStructure([
            'conversation_id',
            'message' => ['id', 'role', 'content', 'created_at'],
            'usage' => ['prompt_tokens', 'completion_tokens'],
        ]);
    
    $this->assertDatabaseHas('conversation_threads', [
        'user_id' => $user->id,
    ]);
}

// Test: Context is maintained across messages
public function test_subsequent_messages_include_conversation_history() {
    // ...
}

// Test: User cannot access another user's conversation
public function test_user_cannot_access_other_user_conversation() {
    // ...
}

// Test: Rate limiting enforced
public function test_rate_limit_returns_429_after_threshold() {
    // ...
}

// Test: Invalid API key returns 401
public function test_invalid_api_key_returns_401() {
    $response = $this->withHeader('X-API-KEY', 'invalid')
        ->postJson('/api/v1/chat/send', ['message' => 'test']);
    $response->assertStatus(401);
}
```

### Step 7.2 — Mock Ollama for Tests

```php
// tests/Mocks/OllamaServiceMock.php
class OllamaServiceMock extends OllamaService {
    public function chat(array $messages, string $model = null, array $options = []): array {
        return [
            'content' => 'This is a mock AI response for testing.',
            'model' => $model ?? 'qwen3:8b',
            'prompt_tokens' => 50,
            'completion_tokens' => 20,
            'response_time_ms' => 100,
        ];
    }
}

// Bind in TestCase
protected function setUp(): void {
    parent::setUp();
    $this->app->instance(OllamaService::class, new OllamaServiceMock());
}
```

---

## Phase 8 — Flutter App API Readiness

**Goal:** Ensure API is Flutter-friendly — proper JSON structure, pagination, error codes.

**Estimated Time:** Half a day (mostly documentation & verification)

### Checklist for Flutter Compatibility

- All responses use consistent JSON envelope: `{ data, meta, error }`
- Pagination uses `page`, `per_page`, `total`, `last_page`
- All timestamps in ISO 8601 format
- Error responses always include `error` (code) and `message` (human readable)
- Streaming endpoint tested with HTTP client that supports SSE (Dart's `http` package + EventSource)
- CORS headers configured for mobile WebView if needed

### Standard Response Envelope

```php
// app/Http/Resources/ApiResponse.php
return response()->json([
    'data' => $payload,
    'meta' => [
        'request_id' => request()->id(),
        'timestamp' => now()->toIso8601String(),
    ],
    'error' => null,
]);

// Error format
return response()->json([
    'data' => null,
    'error' => [
        'code' => 'CONVERSATION_NOT_FOUND',
        'message' => 'The requested conversation does not exist.',
    ],
], 404);
```

---

## Complete API Reference

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/v1/health` | No | Ollama + DB status |
| GET | `/api/v1/auth/me` | Yes | Current user info |
| GET | `/api/v1/conversations` | Yes | List conversations (paginated) |
| POST | `/api/v1/conversations` | Yes | Create conversation |
| GET | `/api/v1/conversations/{id}` | Yes | Get conversation + messages |
| PUT | `/api/v1/conversations/{id}` | Yes | Update title/system prompt |
| DELETE | `/api/v1/conversations/{id}` | Yes | Soft delete |
| GET | `/api/v1/conversations/{id}/messages` | Yes | Messages (paginated) |
| POST | `/api/v1/chat/send` | Yes | Send message (blocking) |
| POST | `/api/v1/chat/stream` | Yes | Send message (SSE stream) |
| GET | `/api/v1/models` | Yes | List available models |
| POST | `/api/v1/models/change` | Yes | Switch model for conversation |
| GET | `/api/v1/admin/metrics` | Admin | Dashboard stats |
| GET | `/api/v1/admin/users` | Admin | User management |
| POST | `/api/v1/admin/users` | Admin | Create API user |

---

## Request/Response Examples

### Create New Chat (Auto-creates conversation)
```json
POST /api/v1/chat/send
Header: X-API-KEY: ak_abc123...

{
  "message": "Explain how Laravel queues work"
}

Response 200:
{
  "conversation_id": 42,
  "message": {
    "id": 87,
    "role": "assistant",
    "content": "Laravel queues allow you to defer time-consuming tasks...",
    "created_at": "2025-01-15T10:30:00Z"
  },
  "usage": {
    "prompt_tokens": 120,
    "completion_tokens": 340,
    "response_time_ms": 2800
  }
}
```

### Continue Existing Conversation
```json
POST /api/v1/chat/send
{
  "conversation_id": 42,
  "message": "Can you show me a code example?"
}
```

### Stream Response (SSE)
```
POST /api/v1/chat/stream
Content-Type: application/json

Response (text/event-stream):
data: {"type":"start","conversation_id":42}

data: {"type":"chunk","content":"Laravel "}
data: {"type":"chunk","content":"queues "}
data: {"type":"chunk","content":"allow..."}

data: {"type":"done","message_id":88,"usage":{"prompt_tokens":120,"completion_tokens":85}}
```

---

## Docker Support (Optional)

```yaml
# docker-compose.yml
version: '3.8'
services:
  app:
    build: .
    ports: ["8000:8000"]
    environment:
      - OLLAMA_BASE_URL=http://host.docker.internal:11434
    volumes:
      - .:/var/www/html
    depends_on: [mysql, redis]
  
  mysql:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: ai_gateway
      MYSQL_USER: ai_gateway_user
      MYSQL_PASSWORD: secret
      MYSQL_ROOT_PASSWORD: root
    volumes:
      - mysql_data:/var/lib/mysql
  
  redis:
    image: redis:alpine

volumes:
  mysql_data:
```

---

## Build Sequence Summary

| Phase | What You Build | Days |
|-------|---------------|------|
| **Pre-setup** | Ollama verify, Ubuntu deps, MySQL, Redis | 0.5 |
| **Phase 1** | Laravel project, migrations, models, API key auth, health endpoint | 1–2 |
| **Phase 2** | OllamaService — chat, stream, model listing | 1 |
| **Phase 3** | ConversationService — context loading, message storage, token tracking | 1–2 |
| **Phase 4** | ChatController — `/chat/send` and `/chat/stream` SSE | 1 |
| **Phase 5** | Nginx + SSL + firewall + production optimize | 0.5 |
| **Phase 6** | Admin dashboard + analytics endpoints | 2–3 |
| **Phase 7** | Tests + Postman collection + OpenAPI spec | 2 |
| **Phase 8** | Flutter API readiness + response envelopes | 0.5 |
| **Total** | | **~10–13 days** |

---

## Security Checklist

- [ ] Ollama bound to `127.0.0.1` only (never public)
- [ ] UFW blocks port 11434 externally
- [ ] API keys hashed in DB (use `bcrypt` or store securely)
- [ ] Rate limiting: 60 req/min per API key via Laravel throttle
- [ ] All user queries scoped to `user_id` (no cross-user data access)
- [ ] Input validated: message length capped at 32K chars
- [ ] SQL injection impossible via Eloquent ORM
- [ ] `X-Accel-Buffering: no` header set for SSE through Nginx
- [ ] Laravel `APP_DEBUG=false` in production
- [ ] Logs do NOT store raw message content in plain server logs (use structured logging)

---

## Future Phases (Reference)

**Phase 2 — RAG Integration**
- Qdrant vector database
- PDF/DOCX upload, chunking, embedding via Ollama embeddings API
- Semantic search before chat context injection

**Phase 3 — CRM Agent**
- Tool calling support in Ollama
- SQL schema introspection
- SELECT-only query execution with LLM validation

**Phase 4 — Multi-Agent Router**
- Dedicated agents: CRM, Sales, Marketing, Support
- Router agent classifies question → dispatches to specialist

---

*Document Version: 1.0 | Based on AI Gateway Platform Technical Specification v1*
