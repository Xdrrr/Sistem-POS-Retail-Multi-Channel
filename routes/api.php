<?php

use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\TokenAuthController;
use Illuminate\Support\Facades\Route;

// Route::post('/token/auth', [TokenAuthController::class, 'auth']);
// Route::get('/token/refresh', [TokenAuthController::class, 'refresh']);

Route::prefix('token')->group(function (): void {
    Route::post('/auth', [TokenAuthController::class, 'auth']);
    Route::post('/refresh', [TokenAuthController::class, 'refresh']);
});

Route::prefix('authentication')->group(function (): void {
    Route::post('/login', [AuthenticationController::class, 'login']);
    Route::post('/user/register', [AuthenticationController::class, 'register']);
});
