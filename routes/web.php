<?php

use App\Http\Controllers\Web\AdminPanelController;
use App\Http\Controllers\Web\ChatbotController;
use App\Http\Controllers\Web\RegisterPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ChatbotController::class, 'index']);
Route::get('/chat', [ChatbotController::class, 'index']);
Route::get('/register', [RegisterPageController::class, 'index']);
Route::get('/admin', [AdminPanelController::class, 'index']);
