<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ProductController;
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
        return view('admin.dashboard');
    })->name('dashboard');

    // Quản lý danh mục (CRUD)
    Route::resource('categories', CategoryController::class);

    // Quản lý người dùng & Khóa/Mở tài khoản
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::patch('/users/{user}/toggle', [UserController::class, 'toggleStatus'])->name('users.toggle');

    Route::resource('products', ProductController::class);
});
require __DIR__ . '/auth.php';
