<?php

use App\Http\Controllers\AuthPageController;
use App\Http\Controllers\CatalogPageController;
use App\Http\Controllers\HomePageController;
use App\Http\Controllers\OrderPageController;
use App\Http\Controllers\ProfilePageController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportPageController;
use App\Http\Controllers\ShiftPageController;
use App\Http\Middleware\EnsureWebAuthenticated;
use App\Http\Middleware\RedirectIfWebAuthenticated;
use Illuminate\Support\Facades\Route;

Route::middleware(RedirectIfWebAuthenticated::class)->group(function (): void {
    Route::get('/login', [AuthPageController::class, 'login'])->name('login');
    Route::post('/login', [AuthPageController::class, 'authenticate'])->name('login.store');
    Route::get('/register', [AuthPageController::class, 'register'])->name('register');
    Route::post('/register', [AuthPageController::class, 'store'])->name('register.store');
});

Route::middleware(EnsureWebAuthenticated::class)->group(function (): void {
    Route::get('/', [HomePageController::class, 'index'])->name('dashboard');

    Route::post('/logout', [AuthPageController::class, 'logout'])->name('logout');

    Route::get('/settings/profile', [ProfilePageController::class, 'edit'])->name('settings.profile');
    Route::put('/settings/profile', [ProfilePageController::class, 'update'])->name('settings.profile.update');

    Route::get('/catalog', [CatalogPageController::class, 'index'])->name('catalog.index');
    Route::post('/catalog/categories', [CatalogPageController::class, 'storeCategory'])->name('catalog.categories.store');
    Route::put('/catalog/categories/{guid}', [CatalogPageController::class, 'updateCategory'])->name('catalog.categories.update');
    Route::delete('/catalog/categories/{guid}', [CatalogPageController::class, 'destroyCategory'])->name('catalog.categories.destroy');
    Route::post('/catalog/groups', [CatalogPageController::class, 'storeGroup'])->name('catalog.groups.store');
    Route::put('/catalog/groups/{guid}', [CatalogPageController::class, 'updateGroup'])->name('catalog.groups.update');
    Route::delete('/catalog/groups/{guid}', [CatalogPageController::class, 'destroyGroup'])->name('catalog.groups.destroy');
    Route::post('/catalog/products', [CatalogPageController::class, 'storeProduct'])->name('catalog.products.store');
    Route::put('/catalog/products/{guid}', [CatalogPageController::class, 'updateProduct'])->name('catalog.products.update');
    Route::delete('/catalog/products/{guid}', [CatalogPageController::class, 'destroyProduct'])->name('catalog.products.destroy');

    Route::get('/orders', [OrderPageController::class, 'index'])->name('orders.index');
    Route::post('/orders/create', [OrderPageController::class, 'store'])->name('orders.store');
    Route::post('/orders/{guid}/payments', [OrderPageController::class, 'storePayment'])->name('orders.payments.store');
    Route::put('/orders/{guid}/complete', [OrderPageController::class, 'complete'])->name('orders.complete');
    Route::put('/orders/{guid}/cancel', [OrderPageController::class, 'cancel'])->name('orders.cancel');

    Route::get('/shifts', [ShiftPageController::class, 'index'])->name('shifts.index');
    Route::get('/shifts/{guid}', [ShiftPageController::class, 'show'])->name('shifts.show');

    Route::get('/reports', [ReportPageController::class, 'index'])->name('reports.index');
    Route::get('/reports/exports', [ReportPageController::class, 'exports'])->name('reports.exports.index');
    Route::post('/reports/exports/history', [ReportController::class, 'exportHistory'])->name('reports.exports.history');
    Route::post('/reports/{type}/preview', [ReportController::class, 'preview'])->name('reports.preview');
    Route::post('/reports/{type}/summary', [ReportController::class, 'summary'])->name('reports.summary');
    Route::post('/reports/{type}/export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('/reports/exports/{guid}', [ReportController::class, 'exportStatus'])->name('reports.exports.status');
    Route::get('/reports/exports/{guid}/download', [ReportController::class, 'download'])->name('reports.exports.download');
});
