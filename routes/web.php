<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PayPalController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;

Route::get('/', function () {
    return redirect()->route('checkout');
});

Route::get('/checkout', [CheckoutController::class, 'index'])
    ->name('checkout');

Route::post('/checkout', [CheckoutController::class, 'store'])
    ->name('checkout.store');

Route::get('/orders', [OrderController::class, 'index'])
    ->name('orders.index');

Route::get('/orders/{order}', [OrderController::class, 'show'])
    ->name('orders.show');

/*
|--------------------------------------------------------------------------
| PayPal
|--------------------------------------------------------------------------
*/

Route::get('/paypal/create/{order}', [PayPalController::class, 'create'])
    ->name('paypal.create');

Route::get('/paypal/success/{order}', [PayPalController::class, 'success'])
    ->name('paypal.success');

Route::get('/paypal/cancel/{order}', [PayPalController::class, 'cancel'])
    ->name('paypal.cancel');

/*
|--------------------------------------------------------------------------
| Admin Orders
|--------------------------------------------------------------------------
*/

Route::get('/admin/orders', [AdminOrderController::class, 'index'])
    ->name('admin.orders.index');

Route::get('/admin/orders/{order}', [AdminOrderController::class, 'show'])
    ->name('admin.orders.show');

Route::patch('/admin/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])
    ->name('admin.orders.update-status');
