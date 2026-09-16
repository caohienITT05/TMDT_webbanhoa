<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\GiftController;
use App\Http\Controllers\ShippingController;


/*
|--------------------------------------------------------------------------
| Trang chủ
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('cart.index');
});


/*
|--------------------------------------------------------------------------
| CART
|--------------------------------------------------------------------------
*/

Route::prefix('cart')->name('cart.')->group(function () {

    Route::get('/', [CartController::class, 'index'])
        ->name('index');

    Route::post('/add', [CartController::class, 'add'])
        ->name('add');

    Route::patch('/update/{id}', [CartController::class, 'update'])
        ->name('update');

    Route::delete('/remove/{id}', [CartController::class, 'remove'])
        ->name('remove');

});


/*
|--------------------------------------------------------------------------
| VOUCHER
|--------------------------------------------------------------------------
*/

Route::prefix('voucher')->name('voucher.')->group(function () {

    Route::post('/apply', [VoucherController::class, 'apply'])
        ->name('apply');

    Route::delete('/remove', [VoucherController::class, 'remove'])
        ->name('remove');

});


/*
|--------------------------------------------------------------------------
| THIỆP + GÓI QUÀ
|--------------------------------------------------------------------------
*/

Route::prefix('gift')->name('gift.')->group(function () {

    Route::post('/save', [GiftController::class, 'save'])
        ->name('save');

    Route::delete('/remove', [GiftController::class, 'remove'])
        ->name('remove');

});


/*
|--------------------------------------------------------------------------
| PHÍ VẬN CHUYỂN
|--------------------------------------------------------------------------
*/

Route::prefix('shipping')->name('shipping.')->group(function () {

    Route::post('/save', [ShippingController::class, 'save'])
        ->name('save');

    Route::delete('/remove', [ShippingController::class, 'remove'])
        ->name('remove');

});


/*
|--------------------------------------------------------------------------
| CHECKOUT
|--------------------------------------------------------------------------
*/

Route::prefix('checkout')->name('checkout.')->group(function () {

    Route::get('/', [CheckoutController::class, 'index'])
        ->name('index');

    Route::post('/process', [CheckoutController::class, 'process'])
        ->name('process');

});