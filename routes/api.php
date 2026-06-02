<?php

use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductGroupController;
use App\Http\Controllers\TokenAuthController;
use App\Http\Middleware\EnsureApiToken;
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

Route::middleware(EnsureApiToken::class)->group(function (): void {
    Route::apiResource('categories', CategoryController::class)->parameters([
        'categories' => 'guid',
    ]);
    Route::apiResource('groups', ProductGroupController::class)->parameters([
        'groups' => 'guid',
    ]);
    Route::apiResource('products', ProductController::class)->parameters([
        'products' => 'guid',
    ]);
});
