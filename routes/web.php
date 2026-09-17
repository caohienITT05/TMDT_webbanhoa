<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CheckoutController;

// 1. Controllers Quản trị (TV1)
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;

// 2. Controllers Giỏ hàng & Dịch vụ quà tặng (TV3)
use App\Http\Controllers\CartController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\GiftController;
use App\Http\Controllers\ShippingController;

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

// Trang chủ
Route::get('/', function () {
    $products = Product::where('is_active', true)->latest()->take(8)->get();
    $categories = Category::all();
    return view('Customer.home', compact('products', 'categories'));
})->name('home');

// Danh mục hoa
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

// Danh sách sản phẩm hoa & Bộ lọc
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

/// Chi tiết hoa (Hỗ trợ cả route name 'product.detail' và 'products.show')
$showProduct = function ($slug) {
    $product = Product::where('slug', $slug)->orWhere('id', $slug)->firstOrFail();
    $relatedProducts = Product::where('category_id', $product->category_id)
        ->where('id', '!=', $product->id)
        ->take(4)
        ->get();
    return view('Customer.product-detail', compact('product', 'relatedProducts'));
};

Route::get('/san-pham/{slug}', $showProduct)->name('product.detail');
Route::get('/chi-tiet-hoa/{slug}', $showProduct)->name('products.show');

// Yêu thích sản phẩm
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

// Đặt hoa theo yêu cầu
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
| PHÂN HỆ GIỎ HÀNG, VOUCHER, QUÀ TẶNG & THANH TOÁN (TV3)
|--------------------------------------------------------------------------
*/
// Phân hệ giỏ hàng (Hỗ trợ cả GET, POST và tham số {id})
Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::match(['get', 'post'], '/add/{id?}', [CartController::class, 'add'])->name('add');
    Route::patch('/update/{id}', [CartController::class, 'update'])->name('update');
    Route::delete('/remove/{id}', [CartController::class, 'remove'])->name('remove');
});

// Route bí danh tương thích cho TV2
Route::match(['get', 'post'], '/gio-hang/them/{id?}', [CartController::class, 'add'])->name('cart.add.alias');
Route::get('/gio-hang', [CartController::class, 'index'])->name('cart');
Route::get('/gio-hang', [CartController::class, 'index'])->name('cart');

// Mã giảm giá (Voucher)
Route::post('/voucher/apply', [VoucherController::class, 'apply'])->name('voucher.apply');
Route::any('/voucher/remove', [VoucherController::class, 'remove'])->name('voucher.remove');

// Quà tặng & Thiệp đính kèm (Khắc phục lỗi gift.save)
Route::post('/gift/save', [GiftController::class, 'save'])->name('gift.save');
Route::any('/gift/remove', [GiftController::class, 'remove'])->name('gift.remove');

// Vận chuyển & Giao hàng (TV3)
Route::post('/shipping/calculate', [ShippingController::class, 'calculate'])->name('shipping.calculate');
Route::post('/shipping/save', [ShippingController::class, 'save'])->name('shipping.save');

// Phân hệ Thanh toán (Checkout)
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'process'])->name('checkout.process');
Route::get('/thanh-toan', [CheckoutController::class, 'index'])->name('checkout');

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