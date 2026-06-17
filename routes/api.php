<?php

use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\InventoryAdjustmentController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductGroupController;
use App\Http\Controllers\ShiftApiController;
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
    Route::get('/categories/{guid}', [CategoryController::class, 'show'])->whereUuid('guid');
    Route::put('/categories/update', [CategoryController::class, 'update']);
    Route::delete('/categories/{guid}', [CategoryController::class, 'destroy'])->whereUuid('guid');
    
    // Groups
    Route::post('/groups', [ProductGroupController::class, 'index']);
    Route::post('/groups/store', [ProductGroupController::class, 'store']);
    Route::get('/groups/{guid}', [ProductGroupController::class, 'show'])->whereUuid('guid');
    Route::put('/groups/update', [ProductGroupController::class, 'update']);
    Route::delete('/groups/{guid}', [ProductGroupController::class, 'destroy'])->whereUuid('guid');
    
    // Products
    Route::post('/products', [ProductController::class, 'index']);
    Route::post('/products/store', [ProductController::class, 'store']);
    Route::get('/products/{guid}', [ProductController::class, 'show'])->whereUuid('guid');
    Route::put('/products/update', [ProductController::class, 'update']);
    Route::delete('/products/{guid}', [ProductController::class, 'destroy'])->whereUuid('guid');

    // Inventory
    Route::post('/inventory', [InventoryController::class, 'index']);
    Route::post('/inventory/store', [InventoryController::class, 'store']);
    Route::get('/inventory/{guid}', [InventoryController::class, 'show'])->whereUuid('guid');
    Route::put('/inventory/update', [InventoryController::class, 'update']);
    Route::delete('/inventory/{guid}', [InventoryController::class, 'destroy'])->whereUuid('guid');
    Route::post('/inventory/adjust', [InventoryAdjustmentController::class, 'adjust']);
    Route::post('/inventory/history', [InventoryAdjustmentController::class, 'history']);

    // Orders
    Route::post('/orders', [OrderController::class, 'index']);
    Route::post('/orders/store', [OrderController::class, 'store']);
    Route::get('/orders/{guid}', [OrderController::class, 'show'])->whereUuid('guid');
    Route::put('/orders/update', [OrderController::class, 'update']);
    Route::delete('/orders/{guid}', [OrderController::class, 'destroy'])->whereUuid('guid');

    // Payments
    Route::post('/payments', [PaymentController::class, 'index']);
    Route::post('/payments/store', [PaymentController::class, 'store']);
    Route::get('/payments/{guid}', [PaymentController::class, 'show'])->whereUuid('guid');

    // Shifts
    Route::prefix('shift')->group(function (): void {
        Route::post('/store', [ShiftApiController::class, 'store']);
        Route::put('/close', [ShiftApiController::class, 'close']);
        Route::get('/active', [ShiftApiController::class, 'active']);
        Route::get('/{guid}', [ShiftApiController::class, 'show'])->whereUuid('guid');
        Route::post('/', [ShiftApiController::class, 'index']);
    });
});
