<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Controllers Quản trị của TV1
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;

// Models
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| GIAO DIỆN KHÁCH HÀNG (TV2)
|--------------------------------------------------------------------------
*/

// 1. Trang chủ
Route::get('/', function () {
    $products = Product::where('is_active', true)->latest()->take(8)->get();
    $categories = Category::all();
    return view('Customer.home', compact('products', 'categories'));
})->name('home');

// 2. Danh mục hoa
Route::get('/danh-muc', function () {
    $categories = Category::withCount('products')->get();
    return view('Customer.categories', compact('categories'));
})->name('categories');

Route::get('/danh-muc-hoa', function () {
    return redirect()->route('categories');
})->name('categories.index');

Route::get('/danh-muc/{slug}', function ($slug) {
    $category = Category::where('slug', $slug)->firstOrFail();
    $products = Product::where('category_id', $category->id)->where('is_active', true)->paginate(12);
    $categories = Category::all();
    return view('Customer.products', compact('products', 'categories', 'category'));
})->name('categories.show');

// 3. Danh sách sản phẩm hoa & Bộ lọc
Route::get('/san-pham', function () {
    $query = Product::where('is_active', true);

    if (request()->filled('category')) {
        $query->where('category_id', request('category'));
    }

    if (request()->filled('search')) {
        $query->where('name', 'like', '%' . request('search') . '%');
    }

    $products = $query->paginate(12)->withQueryString();
    $categories = Category::all();
    return view('Customer.products', compact('products', 'categories'));
})->name('products.index');

Route::get('/products', function () {
    return redirect()->route('products.index');
})->name('products');

// 4. Chi tiết hoa
Route::get('/san-pham/{slug}', function ($slug) {
    $product = Product::where('slug', $slug)->orWhere('id', $slug)->firstOrFail();
    $relatedProducts = Product::where('category_id', $product->category_id)
        ->where('id', '!=', $product->id)
        ->take(4)
        ->get();
    return view('Customer.product-detail', compact('product', 'relatedProducts'));
})->name('products.show');

Route::get('/chi-tiet/{slug}', function ($slug) {
    return redirect()->route('products.show', $slug);
})->name('product.detail');

// 5. Yêu thích sản phẩm (Đã truyền đầy đủ $products & $categories để tránh lỗi undefined)
Route::get('/yeu-thich', function () {
    $favoriteIds = session()->get('favorites', []);
    
    $products = Product::where('is_active', true)
        ->when(!empty($favoriteIds), function ($query) use ($favoriteIds) {
            $query->whereIn('id', $favoriteIds);
        })
        ->paginate(12);

    $categories = Category::all();

    return view('Customer.favorites', compact('products', 'categories'));
})->name('favorites.index');

Route::get('/favorites', function () {
    return redirect()->route('favorites.index');
})->name('favorites');

Route::any('/yeu-thich/them/{id?}', function ($id = null) {
    if ($id) {
        $favorites = session()->get('favorites', []);
        if (!in_array($id, $favorites)) {
            $favorites[] = $id;
            session()->put('favorites', $favorites);
        }
    }
    return back()->with('success', 'Đã thêm hoa vào danh sách yêu thích!');
})->name('favorites.add');

Route::any('/yeu-thich/xoa/{id?}', function ($id = null) {
    if ($id) {
        $favorites = session()->get('favorites', []);
        $favorites = array_diff($favorites, [$id]);
        session()->put('favorites', $favorites);
    }
    return back()->with('success', 'Đã xóa hoa khỏi danh sách yêu thích!');
})->name('favorites.remove');

Route::any('/yeu-thich/destroy/{id?}', function ($id = null) {
    return redirect()->route('favorites.remove', $id);
})->name('favorites.destroy');

Route::any('/yeu-thich/toggle/{id?}', function ($id = null) {
    if ($id) {
        $favorites = session()->get('favorites', []);
        if (in_array($id, $favorites)) {
            $favorites = array_diff($favorites, [$id]);
        } else {
            $favorites[] = $id;
        }
        session()->put('favorites', $favorites);
    }
    return back()->with('success', 'Đã cập nhật danh sách yêu thích!');
})->name('favorites.toggle');

// 6. Giỏ hàng & Đặt hàng theo yêu cầu (Đã truyền sẵn $cart và $products gợi ý)
Route::get('/gio-hang', function () {
    $cart = session()->get('cart', []);
    $products = Product::where('is_active', true)->take(4)->get();
    return view('Customer.cart', compact('cart', 'products'));
})->name('cart.index');

Route::get('/cart', function () {
    return redirect()->route('cart.index');
})->name('cart');

Route::any('/gio-hang/them/{id?}', function ($id = null) {
    return back()->with('success', 'Đã thêm hoa vào giỏ hàng!');
})->name('cart.add');

Route::any('/gio-hang/xoa/{id?}', function ($id = null) {
    return back()->with('success', 'Đã xóa sản phẩm khỏi giỏ hàng!');
})->name('cart.remove');

Route::get('/dat-hoa-theo-yeu-cau', function () {
    return view('Customer.custom-order');
})->name('custom.order');

Route::get('/custom-order', function () {
    return redirect()->route('custom.order');
})->name('custom-order');

Route::post('/dat-hoa-theo-yeu-cau', function () {
    return back()->with('success', 'BloomGift đã tiếp nhận yêu cầu cắm hoa riêng của bạn!');
})->name('custom.order.store');

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