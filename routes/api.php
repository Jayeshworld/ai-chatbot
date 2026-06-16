<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\ModelController;
use App\Http\Controllers\Api\RegisterController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Public
    Route::get('/health', [HealthController::class, 'check']);
    Route::post('/auth/register', [RegisterController::class, 'sendOtp']);
    Route::post('/auth/verify-otp', [RegisterController::class, 'verifyOtp']);

    // Authenticated
    Route::middleware(['api.auth', 'throttle:60,1'])->group(function () {

        Route::get('/auth/me', [AuthController::class, 'me']);

        Route::get('/conversations', [ConversationController::class, 'index']);
        Route::post('/conversations', [ConversationController::class, 'store']);
        Route::get('/conversations/{id}', [ConversationController::class, 'show']);
        Route::put('/conversations/{id}', [ConversationController::class, 'update']);
        Route::delete('/conversations/{id}', [ConversationController::class, 'destroy']);
        Route::get('/conversations/{id}/messages', [MessageController::class, 'index']);

        Route::post('/chat/send', [ChatController::class, 'send']);
        Route::post('/chat/stream', [ChatController::class, 'stream']);

        Route::get('/models', [ModelController::class, 'index']);
        Route::post('/models/change', [ModelController::class, 'change']);
    });

    // Admin
    Route::middleware(['api.auth', 'admin'])->prefix('admin')->group(function () {
        Route::get('/metrics', [AdminController::class, 'metrics']);
        Route::get('/users', [AdminController::class, 'users']);
        Route::post('/users', [AdminController::class, 'createUser']);
        Route::put('/users/{id}/toggle', [AdminController::class, 'toggleUser']);
        Route::get('/usage', [AdminController::class, 'usage']);
        Route::get('/models', [AdminController::class, 'models']);
        Route::post('/models/{id}/toggle', [AdminController::class, 'toggleModel']);
        Route::get('/registration-requests', [AdminController::class, 'registrationRequests']);
        Route::put('/registration-requests/{id}/approve', [AdminController::class, 'approveRequest']);
        Route::put('/registration-requests/{id}/reject', [AdminController::class, 'rejectRequest']);
    });
});
