<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ProductController;

use App\Http\Controllers\Admin\OrderController;

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
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
// Nhóm route quản trị BloomGift (Admin)
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', function () {
        $totalRevenue = Order::where('status', 'COMPLETED')->sum('total_amount');
        $totalOrders = Order::count();
        $pendingOrders = Order::whereIn('status', ['PENDING', 'CONFIRMED', 'PREPARING'])->count();
        $totalProducts = Product::count();
        $totalCustomers = User::where('role', 'customer')->count();
        $recentOrders = Order::with('user')->latest()->take(5)->get();

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
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::patch('/users/{user}/toggle', [UserController::class, 'toggleStatus'])->name('users.toggle');
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::patch('/users/{user}/toggle', [UserController::class, 'toggleStatus'])->name('users.toggle');
});
require __DIR__ . '/auth.php';
