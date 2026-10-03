<?php

use App\Presentation\Http\Controllers\Admin\DashboardController;
use App\Presentation\Http\Controllers\Api\CashierApiController;
use App\Presentation\Http\Controllers\Api\CategoryApiController;
use App\Presentation\Http\Controllers\Api\ProductApiController;
use App\Presentation\Http\Controllers\Api\ReportApiController;
use App\Presentation\Http\Controllers\Api\StockInApiController;
use App\Presentation\Http\Controllers\Auth\ChangePasswordController;
use App\Presentation\Http\Controllers\Auth\ForgotPasswordController;
use App\Presentation\Http\Controllers\Auth\LoginController;
use App\Presentation\Http\Controllers\Auth\RegisterController;
use App\Presentation\Http\Controllers\CashierController;
use App\Presentation\Http\Controllers\CategoryController;
use App\Presentation\Http\Controllers\Payment\PaymentSimulationController;
use App\Presentation\Http\Controllers\Payment\XenditWebhookController;
use App\Presentation\Http\Controllers\ProductController;
use App\Presentation\Http\Controllers\ReportController;
use App\Presentation\Http\Controllers\StockInController;
use App\Presentation\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');

    // Forgot password: email a link, the link opens /change-password?token=…
    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/change-password', [ChangePasswordController::class, 'create'])->name('password.reset');
    Route::post('/change-password', [ChangePasswordController::class, 'store'])->middleware('throttle:10,1')->name('password.update');

    // Registration: verify the email with a code, then owner + store + password.
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register/code', [RegisterController::class, 'sendCode'])->middleware('throttle:5,1')->name('register.code');
    Route::post('/register/verify', [RegisterController::class, 'verifyCode'])->middleware('throttle:10,1')->name('register.verify');
    Route::post('/register/restart', [RegisterController::class, 'restart'])->name('register.restart');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:5,1')->name('register.store');
});

// Payment gateway side. No login: Xendit calls the webhook, and an invoice
// page can be opened from any browser. CSRF is skipped for webhooks/* only.
Route::post('/webhooks/xendit/invoice', XenditWebhookController::class)->middleware('throttle:60,1')->name('webhooks.xendit');
Route::get('/pay/simulate/{payment}', [PaymentSimulationController::class, 'show'])->name('payments.simulate');
Route::post('/pay/simulate/{payment}', [PaymentSimulationController::class, 'pay'])->name('payments.simulate.pay');
Route::post('/pay/simulate/{payment}/cancel', [PaymentSimulationController::class, 'cancel'])->name('payments.simulate.cancel');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});

// The app owner's dashboard: every store, read-only.
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
});

Route::middleware(['auth', 'store'])->group(function () {

    // Open whatever the subscription status: choose a plan, pay, renew.
    Route::get('/subscription', [SubscriptionController::class, 'index'])->name('subscription.index');
    Route::post('/subscription/checkout', [SubscriptionController::class, 'checkout'])->middleware('throttle:10,1')->name('subscription.checkout');
    Route::get('/subscription/finish/{payment}', [SubscriptionController::class, 'finish'])->name('subscription.finish');
    Route::get('/subscription/expired', [SubscriptionController::class, 'expired'])->name('subscription.expired');
});

// Everything below needs a running subscription.
Route::middleware(['auth', 'store', 'subscribed'])->group(function () {

    // Inertia page shells. They also hand the first payload to TanStack Query.
    Route::get('/', [CashierController::class, 'index'])->name('cashier');
    Route::get('/products', [ProductController::class, 'index'])->name('products');
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit')->whereNumber('product');
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories');
    Route::get('/stock-in', [StockInController::class, 'index'])->name('stock-in');
    Route::get('/report', [ReportController::class, 'index'])->name('report');

    // JSON API used by TanStack Query. It lives in the web group on purpose:
    // the same session cookie and CSRF token as the pages, no extra auth layer.
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/cashier/products', [CashierApiController::class, 'index'])->name('cashier.products');
        Route::post('/sales', [CashierApiController::class, 'store'])->name('sales.store');

        Route::get('/products', [ProductApiController::class, 'index'])->name('products');
        Route::get('/products/similar', [ProductApiController::class, 'similar'])->name('products.similar');
        Route::post('/products', [ProductApiController::class, 'store'])->name('products.store');
        Route::put('/products/{product}', [ProductApiController::class, 'update'])->name('products.update')->whereNumber('product');

        Route::get('/categories', [CategoryApiController::class, 'index'])->name('categories');
        Route::get('/categories/options', [CategoryApiController::class, 'options'])->name('categories.options');
        Route::post('/categories', [CategoryApiController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [CategoryApiController::class, 'update'])->name('categories.update')->whereNumber('category');
        Route::delete('/categories/{category}', [CategoryApiController::class, 'destroy'])->name('categories.destroy')->whereNumber('category');

        Route::get('/stock-in/history', [StockInApiController::class, 'index'])->name('stock-in.history');
        Route::post('/stock-in', [StockInApiController::class, 'store'])->name('stock-in.store');

        Route::get('/report', [ReportApiController::class, 'index'])->name('report');
    });
});
