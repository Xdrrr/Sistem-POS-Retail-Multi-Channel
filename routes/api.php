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
    // Categories
    Route::post('/categories', [CategoryController::class, 'index']);
    Route::post('/categories/store', [CategoryController::class, 'store']);
    Route::get('/categories/{guid}', [CategoryController::class, 'show']);
    Route::put('/categories/update', [CategoryController::class, 'update']);
    Route::delete('/categories/{guid}', [CategoryController::class, 'destroy']);
    
    // Groups
    Route::post('/groups', [ProductGroupController::class, 'index']);
    Route::post('/groups/store', [ProductGroupController::class, 'store']);
    Route::get('/groups/{guid}', [ProductGroupController::class, 'show']);
    Route::put('/groups/update', [ProductGroupController::class, 'update']);
    Route::delete('/groups/{guid}', [ProductGroupController::class, 'destroy']);
    
    // Products
    Route::post('/products', [ProductController::class, 'index']);
    Route::post('/products/store', [ProductController::class, 'store']);
    Route::get('/products/{guid}', [ProductController::class, 'show']);
    Route::put('/products/update', [ProductController::class, 'update']);
    Route::delete('/products/{guid}', [ProductController::class, 'destroy']);
});
