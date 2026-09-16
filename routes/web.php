<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PayPalController;

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| CUSTOMER - CHECKOUT / ORDERS / PAYPAL
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    Route::get('/checkout', [CheckoutController::class, 'index'])
        ->name('checkout');

    Route::post('/checkout', [CheckoutController::class, 'store'])
        ->name('checkout.store');

    Route::get('/orders', [OrderController::class, 'index'])
        ->name('orders.index');

    Route::get('/orders/{order}', [OrderController::class, 'show'])
        ->name('orders.show');

    Route::patch('/orders/{order}/cancel', [OrderController::class, 'cancel'])
        ->name('orders.cancel');

    Route::get('/paypal/create/{order}', [PayPalController::class, 'create'])
        ->name('paypal.create');

    Route::get('/paypal/success/{order}', [PayPalController::class, 'success'])
        ->name('paypal.success');

    Route::get('/paypal/cancel/{order}', [PayPalController::class, 'cancel'])
        ->name('paypal.cancel');
});

/*
|--------------------------------------------------------------------------
| ADMIN
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/dashboard', function () {
            $totalRevenue = Order::where('order_status', 'completed')
                ->sum('total');

            $totalOrders = Order::count();

            $pendingOrders = Order::whereIn(
                'order_status',
                ['pending', 'confirmed', 'processing']
            )->count();

            $totalProducts = Product::count();

            $totalCustomers = User::where('role', 'customer')->count();

            $recentOrders = Order::latest()
                ->take(5)
                ->get();

            return view('admin.dashboard', compact(
                'totalRevenue',
                'totalOrders',
                'pendingOrders',
                'totalProducts',
                'totalCustomers',
                'recentOrders'
            ));
        })->name('dashboard');

        Route::resource('categories', CategoryController::class);

        Route::resource('products', ProductController::class);

        Route::get('/orders', [AdminOrderController::class, 'index'])
            ->name('orders.index');

        Route::get('/orders/{order}', [AdminOrderController::class, 'show'])
            ->name('orders.show');

        Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])
            ->name('orders.update-status');

        Route::get('/users', [UserController::class, 'index'])
            ->name('users.index');

        Route::get('/users/{user}', [UserController::class, 'show'])
            ->name('users.show');

        Route::patch('/users/{user}/toggle', [UserController::class, 'toggleStatus'])
            ->name('users.toggle');
    });

require __DIR__ . '/auth.php';