<?php

use App\Presentation\Http\Controllers\Api\CashierApiController;
use App\Presentation\Http\Controllers\Api\ProductApiController;
use App\Presentation\Http\Controllers\Api\ReportApiController;
use App\Presentation\Http\Controllers\Api\StockInApiController;
use App\Presentation\Http\Controllers\Auth\LoginController;
use App\Presentation\Http\Controllers\CashierController;
use App\Presentation\Http\Controllers\ProductController;
use App\Presentation\Http\Controllers\ReportController;
use App\Presentation\Http\Controllers\StockInController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // Inertia page shells. They also hand the first payload to TanStack Query.
    Route::get('/', [CashierController::class, 'index'])->name('cashier');
    Route::get('/products', [ProductController::class, 'index'])->name('products');
    Route::get('/stock-in', [StockInController::class, 'index'])->name('stock-in');
    Route::get('/report', [ReportController::class, 'index'])->name('report');

    // JSON API used by TanStack Query. It lives in the web group on purpose:
    // the same session cookie and CSRF token as the pages, no extra auth layer.
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/cashier/products', [CashierApiController::class, 'index'])->name('cashier.products');
        Route::post('/sales', [CashierApiController::class, 'store'])->name('sales.store');

        Route::get('/products', [ProductApiController::class, 'index'])->name('products');
        Route::post('/products', [ProductApiController::class, 'store'])->name('products.store');
        Route::put('/products/{product}', [ProductApiController::class, 'update'])->name('products.update')->whereNumber('product');

        Route::get('/stock-in/history', [StockInApiController::class, 'index'])->name('stock-in.history');
        Route::post('/stock-in', [StockInApiController::class, 'store'])->name('stock-in.store');

        Route::get('/report', [ReportApiController::class, 'index'])->name('report');
    });
});
