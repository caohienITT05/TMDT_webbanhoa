<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Controllers Quản trị của bạn (TV1)
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

// Controllers Khách hàng của TV2 (Sản phẩm & Danh mục)
use App\Http\Controllers\CategoryController as CustomerCategoryController;
// (Nếu TV2 để trong thư mục Customer, Laravel sẽ tự tìm theo namespace)

/*
|--------------------------------------------------------------------------
| GIAO DIỆN KHÁCH HÀNG (TV2)
|--------------------------------------------------------------------------
*/
// Nối thẳng trang chủ hiển thị danh sách hoa của TV2
Route::get('/', function () {
    $products = Product::where('is_active', true)->latest()->take(8)->get();
    return view('welcome', compact('products'));
})->name('home');

// Xem sản phẩm & danh mục phía người mua (TV2)
Route::get('/san-pham', function () {
    $products = Product::where('is_active', true)->paginate(12);
    return view('Customer.index', compact('products'));
})->name('products.index');

/*
|--------------------------------------------------------------------------
| TÀI KHOẢN & BREEZE AUTH (TV1)
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| PHÂN HỆ QUẢN TRỊ VIÊN - ADMIN PORTAL (TV1)
|--------------------------------------------------------------------------
*/
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

    Route::resource('categories', AdminCategoryController::class);
    Route::resource('products', AdminProductController::class);
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.update-status');
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
    Route::patch('/users/{user}/toggle', [AdminUserController::class, 'toggleStatus'])->name('users.toggle');
});

require __DIR__ . '/auth.php';