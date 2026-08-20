<?php

use App\Http\Controllers\AuthPageController;
use App\Http\Controllers\CabangPageController;
use App\Http\Controllers\CatalogPageController;
use App\Http\Controllers\HomePageController;
use App\Http\Controllers\InventoryPageController;
use App\Http\Controllers\OrderPageController;
use App\Http\Controllers\PermissionPageController;
use App\Http\Controllers\ProfilePageController;
use App\Http\Controllers\TableReservationPageController;
use App\Http\Controllers\TablesPageController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportPageController;
use App\Http\Controllers\RolePageController;
use App\Http\Controllers\ShiftPageController;
use App\Http\Controllers\UserPageController;
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
    Route::get('/', [HomePageController::class, 'index'])->name('dashboard')->middleware('permission:menu.dashboard');

    Route::post('/logout', [AuthPageController::class, 'logout'])->name('logout');

    Route::get('/settings/profile', [ProfilePageController::class, 'edit'])->name('settings.profile');
    Route::put('/settings/profile', [ProfilePageController::class, 'update'])->name('settings.profile.update');

    Route::get('/catalog', [CatalogPageController::class, 'index'])->name('catalog.index')->middleware('permission:menu.catalog');
    Route::post('/catalog/categories', [CatalogPageController::class, 'storeCategory'])->name('catalog.categories.store')->middleware('permission:menu.catalog');
    Route::put('/catalog/categories/{guid}', [CatalogPageController::class, 'updateCategory'])->name('catalog.categories.update')->middleware('permission:menu.catalog');
    Route::delete('/catalog/categories/{guid}', [CatalogPageController::class, 'destroyCategory'])->name('catalog.categories.destroy')->middleware('permission:menu.catalog');
    Route::post('/catalog/groups', [CatalogPageController::class, 'storeGroup'])->name('catalog.groups.store')->middleware('permission:menu.catalog');
    Route::put('/catalog/groups/{guid}', [CatalogPageController::class, 'updateGroup'])->name('catalog.groups.update')->middleware('permission:menu.catalog');
    Route::delete('/catalog/groups/{guid}', [CatalogPageController::class, 'destroyGroup'])->name('catalog.groups.destroy')->middleware('permission:menu.catalog');
    Route::post('/catalog/products', [CatalogPageController::class, 'storeProduct'])->name('catalog.products.store')->middleware('permission:menu.catalog');
    Route::put('/catalog/products/{guid}', [CatalogPageController::class, 'updateProduct'])->name('catalog.products.update')->middleware('permission:menu.catalog');
    Route::delete('/catalog/products/{guid}', [CatalogPageController::class, 'destroyProduct'])->name('catalog.products.destroy')->middleware('permission:menu.catalog');

    Route::get('/inventory', [InventoryPageController::class, 'index'])->name('inventory.index')->middleware('permission:menu.inventory');
    Route::post('/inventory/items', [InventoryPageController::class, 'store'])->name('inventory.store')->middleware('permission:menu.inventory');
    Route::put('/inventory/items/{guid}', [InventoryPageController::class, 'update'])->name('inventory.update')->middleware('permission:menu.inventory');
    Route::delete('/inventory/items/{guid}', [InventoryPageController::class, 'destroy'])->name('inventory.destroy')->middleware('permission:menu.inventory');
    Route::post('/inventory/items/adjust', [InventoryPageController::class, 'adjust'])->name('inventory.adjust')->middleware('permission:menu.inventory');
    Route::get('/inventory/history', [InventoryPageController::class, 'historyIndex'])->name('inventory.history.index')->middleware('permission:menu.inventory');
    Route::get('/inventory/items/{guid}/history', [InventoryPageController::class, 'history'])->name('inventory.history.show')->middleware('permission:menu.inventory');

    Route::get('/orders', [OrderPageController::class, 'index'])->name('orders.index')->middleware('permission:menu.orders');
    Route::post('/orders/create', [OrderPageController::class, 'store'])->name('orders.store')->middleware('permission:menu.orders');
    Route::post('/orders/{guid}/payments', [OrderPageController::class, 'storePayment'])->name('orders.payments.store')->middleware('permission:menu.orders');
    Route::put('/orders/{guid}/complete', [OrderPageController::class, 'complete'])->name('orders.complete')->middleware('permission:menu.orders');
    Route::put('/orders/{guid}/cancel', [OrderPageController::class, 'cancel'])->name('orders.cancel')->middleware('permission:menu.orders');

    Route::get('/shifts', [ShiftPageController::class, 'index'])->name('shifts.index')->middleware('permission:menu.shift');
    Route::get('/shifts/{guid}', [ShiftPageController::class, 'show'])->name('shifts.show')->middleware('permission:menu.shift');

    Route::get('/users', [UserPageController::class, 'index'])->name('users.index')->middleware('permission:menu.users');
    Route::post('/users/items', [UserPageController::class, 'store'])->name('users.store')->middleware('permission:menu.users');
    Route::put('/users/items/{guid}', [UserPageController::class, 'update'])->name('users.update')->middleware('permission:menu.users');
    Route::delete('/users/items/{guid}', [UserPageController::class, 'destroy'])->name('users.destroy')->middleware('permission:menu.users');

    Route::get('/roles', [RolePageController::class, 'index'])->name('roles.index')->middleware('permission:menu.roles');
    Route::post('/roles/items', [RolePageController::class, 'store'])->name('roles.store')->middleware('permission:menu.roles');
    Route::put('/roles/items/{guid}', [RolePageController::class, 'update'])->name('roles.update')->middleware('permission:menu.roles');
    Route::delete('/roles/items/{guid}', [RolePageController::class, 'destroy'])->name('roles.destroy')->middleware('permission:menu.roles');

    Route::get('/permissions', [PermissionPageController::class, 'index'])->name('permissions.index')->middleware('permission:menu.roles');
    Route::put('/permissions/role/{guid}', [PermissionPageController::class, 'update'])->name('permissions.update')->middleware('permission:menu.roles');

    Route::get('/cabang', [CabangPageController::class, 'index'])->name('cabang.index')->middleware('permission:menu.cabang');
    Route::post('/cabang/items', [CabangPageController::class, 'store'])->name('cabang.store')->middleware('permission:menu.cabang');
    Route::put('/cabang/items/{guid}', [CabangPageController::class, 'update'])->name('cabang.update')->middleware('permission:menu.cabang');
    Route::delete('/cabang/items/{guid}', [CabangPageController::class, 'destroy'])->name('cabang.destroy')->middleware('permission:menu.cabang');

    Route::get('/tables', [TablesPageController::class, 'index'])->name('tables.index')->middleware('permission:menu.tables');
    Route::post('/tables/items', [TablesPageController::class, 'store'])->name('tables.store')->middleware('permission:menu.tables');
    Route::put('/tables/items/{guid}', [TablesPageController::class, 'update'])->name('tables.update')->middleware('permission:menu.tables');
    Route::delete('/tables/items/{guid}', [TablesPageController::class, 'destroy'])->name('tables.destroy')->middleware('permission:menu.tables');

    Route::get('/reservations', [TableReservationPageController::class, 'index'])->name('reservations.index')->middleware('permission:menu.reservation');
    Route::post('/reservations/items', [TableReservationPageController::class, 'store'])->name('reservations.store')->middleware('permission:menu.reservation');
    Route::put('/reservations/items/{guid}', [TableReservationPageController::class, 'update'])->name('reservations.update')->middleware('permission:menu.reservation');
    Route::delete('/reservations/items/{guid}', [TableReservationPageController::class, 'destroy'])->name('reservations.destroy')->middleware('permission:menu.reservation');
    Route::post('/reservations/{guid}/release', [TableReservationPageController::class, 'release'])->name('reservations.release')->middleware('permission:menu.reservation');
    Route::post('/orders/{guid}/release-table', [TableReservationPageController::class, 'releaseOrderTable'])->name('orders.release-table')->middleware('permission:menu.reservation');

    Route::get('/reports', [ReportPageController::class, 'index'])->name('reports.index')->middleware('permission:menu.reports');
    Route::get('/reports/exports', [ReportPageController::class, 'exports'])->name('reports.exports.index')->middleware('permission:menu.exports');
    Route::post('/reports/exports/history', [ReportController::class, 'exportHistory'])->name('reports.exports.history')->middleware('permission:menu.exports');
    Route::post('/reports/{type}/preview', [ReportController::class, 'preview'])->name('reports.preview')->middleware('permission:menu.reports');
    Route::post('/reports/{type}/summary', [ReportController::class, 'summary'])->name('reports.summary')->middleware('permission:menu.reports');
    Route::post('/reports/{type}/export', [ReportController::class, 'export'])->name('reports.export')->middleware('permission:menu.reports');
    Route::get('/reports/exports/{guid}', [ReportController::class, 'exportStatus'])->name('reports.exports.status')->middleware('permission:menu.reports');
    Route::get('/reports/exports/{guid}/download', [ReportController::class, 'download'])->name('reports.exports.download')->middleware('permission:menu.reports');
});
