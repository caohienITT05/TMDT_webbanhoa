<?php

use App\Http\Controllers\Admin\FinanceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->prefix('admin/finance')->name('admin.finance.')->group(function () {
    Route::get('/', [FinanceController::class, 'index'])->name('index');
    Route::get('/export', [FinanceController::class, 'export'])->name('export');
    Route::get('/orders', [FinanceController::class, 'orders'])->name('orders');
    Route::get('/orders/{order}', [FinanceController::class, 'order'])->name('order');
    Route::put('/orders/{order}', [FinanceController::class, 'saveOrder'])->name('order.save');
    Route::get('/settings', [FinanceController::class, 'settings'])->name('settings');
    Route::post('/settings', [FinanceController::class, 'saveSettings'])->name('settings.save');
    Route::get('/expenses', [FinanceController::class, 'expenses'])->name('expenses');
    Route::post('/expenses', [FinanceController::class, 'saveExpense'])->name('expenses.save');
    Route::get('/taxes', [FinanceController::class, 'taxes'])->name('taxes');
    Route::post('/tax-settings', [FinanceController::class, 'saveTaxSetting'])->name('tax-settings.save');
    Route::patch('/tax-settings/{setting}', [FinanceController::class, 'closeTaxSetting'])->name('tax-settings.close');
    Route::post('/tax-records', [FinanceController::class, 'saveTaxRecord'])->name('tax-records.save');
    Route::post('/product-tax-profiles', [FinanceController::class, 'saveProductTaxProfile'])->name('product-tax-profiles.save');
});
