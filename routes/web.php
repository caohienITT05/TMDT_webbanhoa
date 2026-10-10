<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\VoucherController as AdminVoucherController;
use App\Http\Controllers\PolicyController;
use App\Http\Controllers\SitemapController;

// 1. Controllers Quản trị (TV1 & TV4)
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\DeliverySlotController as AdminDeliverySlotController;
use App\Http\Controllers\Admin\CustomOrderController as AdminCustomOrderController;

// 2. Controllers Giỏ hàng, Quà tặng & Voucher (TV3)
use App\Http\Controllers\CartController;
use App\Http\Controllers\BuyNowController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\GiftController;
use App\Http\Controllers\GiftAdvisorController;
use App\Http\Controllers\ShippingController;

// 3. Controllers Đặt hàng & Thanh toán PayPal (TV4)
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\Customer\CustomOrderController as CustomerCustomOrderController;

// Models
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

use Illuminate\Support\Facades\DB;

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
    $categories = Category::query()
        ->whereNull('parent_id')
        ->with([
            'children' => fn ($query) => $query->withCount('products')->orderBy('name'),
        ])
        ->withCount('products')
        ->orderBy('name')
        ->get();

    return view('Customer.categories', compact('categories'));
})->name('categories');

Route::get('/danh-muc-hoa', function () {
    return redirect()->route('categories');
})->name('categories.index');

Route::get('/danh-muc/{slug}', function ($slug) {
    $category = Category::with('children')->where('slug', $slug)->firstOrFail();
    $products = Product::where('category_id', $category->id)->where('is_active', true)->paginate(12);
    $categories = Category::with('parent')->orderBy('parent_id')->orderBy('name')->get();
    return view('Customer.products', compact('products', 'categories', 'category'));
})->name('categories.show');

// Danh sách hoa & Tìm kiếm
Route::get('/san-pham', function () {
    $query = Product::where('is_active', true);

    if (request()->filled('category')) {
        $query->where('category_id', request('category'));
    }

    if (request()->filled('search')) {
        $query->where('name', 'like', '%' . request('search') . '%');
    }

    if (is_numeric(request('min_price'))) {
        $query->where('price', '>=', max(0, (float) request('min_price')));
    }

    if (is_numeric(request('max_price'))) {
        $query->where('price', '<=', max(0, (float) request('max_price')));
    }

    if (in_array(request('season'), Product::SEASONS, true)) {
        $query->season(request('season'));
    }

    if (request()->boolean('in_stock')) {
        $query->where('stock', '>', 0);
    }

    if (request()->boolean('sale')) {
        $query->whereNotNull('sale_price')->whereColumn('sale_price', '<', 'price');
    }

    match (request('sort')) {
        'price_asc' => $query->orderBy('price'),
        'price_desc' => $query->orderByDesc('price'),
        'newest' => $query->latest(),
        default => $query->orderBy('id'),
    };

    $products = $query->paginate(12)->withQueryString();
    $categories = Category::with('parent')->orderBy('parent_id')->orderBy('name')->get();
    return view('Customer.products', compact('products', 'categories'));
})->name('products.index');

Route::get('/products', function () {
    return redirect()->route('products.index');
})->name('products');

// Chi tiết hoa
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
Route::get('/dat-hoa-theo-yeu-cau', [CustomerCustomOrderController::class, 'create'])->name('custom.order');

Route::get('/custom-order', function () {
    return redirect()->route('custom.order');
})->name('custom-order');

Route::post('/dat-hoa-theo-yeu-cau', [CustomerCustomOrderController::class, 'store'])->name('custom.order.store');

/*
|--------------------------------------------------------------------------
| PHÂN HỆ GIỎ HÀNG, QUÀ TẶNG & VOUCHER (TV3)
|--------------------------------------------------------------------------
*/
Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::match(['get', 'post'], '/add/{id?}', [CartController::class, 'add'])->name('add');
    Route::patch('/update/{id}', [CartController::class, 'update'])->name('update');
    Route::delete('/remove/{id}', [CartController::class, 'remove'])->name('remove');
});

Route::match(['get', 'post'], '/gio-hang/them/{id?}', [CartController::class, 'add'])->name('cart.add.alias');
Route::get('/gio-hang', [CartController::class, 'index'])->name('cart');
Route::post('/mua-ngay/{product}', [BuyNowController::class, 'start'])->name('buy-now.start');

Route::post('/voucher/apply', [VoucherController::class, 'apply'])->name('voucher.apply');
Route::any('/voucher/remove', [VoucherController::class, 'remove'])->name('voucher.remove');
Route::post('/tro-ly-chon-hoa-qua/goi-y', [GiftAdvisorController::class, 'suggest'])->name('gift-advisor.suggest');
Route::post('/gift/save', [GiftController::class, 'save'])->name('gift.save');
Route::any('/gift/remove', [GiftController::class, 'remove'])->name('gift.remove');
Route::post('/shipping/calculate', [ShippingController::class, 'calculate'])->name('shipping.calculate');
Route::post('/shipping/save', [ShippingController::class, 'save'])->name('shipping.save');

/*
|--------------------------------------------------------------------------
| PHÂN HỆ THANH TOÁN & PAYPAL (TV4) - BẮT BUỘC ĐĂNG NHẬP
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::get('/mua-ngay', [CheckoutController::class, 'buyNow'])->name('buy-now.checkout');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::post('/checkout/process', [CheckoutController::class, 'store'])->name('checkout.process');
    Route::get('/thanh-toan', [CheckoutController::class, 'index'])->name('checkout');

    // Route thanh toán PayPal
    Route::get('/paypal/create/{order}', [CheckoutController::class, 'paypalCreate'])->name('paypal.create');
    Route::get('/paypal/success', [CheckoutController::class, 'paypalSuccess'])->name('paypal.success');
    Route::get('/paypal/cancel', [CheckoutController::class, 'paypalCancel'])->name('paypal.cancel');

    // Trang xem đơn hàng sau khi đặt thành công
    Route::get('/orders/{order}', [CustomerOrderController::class, 'show'])->name('orders.show');

    // Xem danh sách và theo dõi đơn hàng của khách hàng (UC14)
    Route::get('/don-hang-cua-toi', function () {
        $orders = Order::where('user_id', auth()->id())
            ->with(['orderItems.product', 'deliverySlot'])
            ->latest()
            ->paginate(10);
        return view('Customer.orders', compact('orders'));
    })->name('customer.orders');

    Route::get('/yeu-cau-cua-toi', [CustomerCustomOrderController::class, 'index'])->name('customer.custom-orders.index');
    Route::get('/yeu-cau-cua-toi/{customOrderRequest}', [CustomerCustomOrderController::class, 'show'])->name('customer.custom-orders.show');
});

/*
|--------------------------------------------------------------------------
| TÀI KHOẢN & BREEZE AUTH (TV1)
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', function () {
    if (auth()->user()->role === 'admin') {
        return redirect()->route('admin.dashboard');
    }
    return redirect()->route('home');
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| PHÂN HỆ QUẢN TRỊ VIÊN - ADMIN PORTAL (TV1 & TV4)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {

    // Điểm vào ngắn gọn cho khu vực quản trị.
    Route::redirect('/', '/admin/dashboard')->name('home');

    // Bảng điều khiển quản trị & Thống kê doanh thu tự động
    Route::get('/dashboard', function () {
        // TÍNH DOANH THU TRIỆT ĐỂ:
        // 1. Đơn thanh toán online thành công (payment_status = 'paid')
        // 2. Đơn COD đã hoàn thành/đã giao (COMPLETED / DELIVERED)
        // 3. Đơn đã duyệt (CONFIRMED)
        $totalRevenue = Order::where(function ($query) {
            $query->where('payment_status', 'paid')
                ->orWhereIn('status', ['COMPLETED', 'completed', 'DELIVERED', 'delivered', 'CONFIRMED', 'confirmed'])
                ->orWhereIn('order_status', ['completed', 'delivered', 'confirmed']);
        })
            ->sum(DB::raw('COALESCE(total_amount, total, 0)'));

        $totalOrders = Order::count();

        // Đếm số đơn đang chờ xử lý & cắm hoa
        $pendingOrders = Order::where(function ($query) {
            $query->whereIn('status', ['PENDING', 'pending', 'PREPARING', 'preparing', 'processing'])
                ->orWhereIn('order_status', ['pending', 'preparing', 'processing']);
        })->count();

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

    // Quản lý khung giờ giao nhận hoa
    Route::resource('delivery-slots', AdminDeliverySlotController::class);

    Route::resource('categories', AdminCategoryController::class);
    Route::resource('products', AdminProductController::class);
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.update-status');
    Route::get('/custom-orders', [AdminCustomOrderController::class, 'index'])->name('custom-orders.index');
    Route::get('/custom-orders/{customOrderRequest}', [AdminCustomOrderController::class, 'show'])->name('custom-orders.show');
    Route::patch('/custom-orders/{customOrderRequest}/contacted', [AdminCustomOrderController::class, 'contacted'])->name('custom-orders.contacted');
    Route::patch('/custom-orders/{customOrderRequest}/accept', [AdminCustomOrderController::class, 'accept'])->name('custom-orders.accept');
    Route::patch('/custom-orders/{customOrderRequest}/reject', [AdminCustomOrderController::class, 'reject'])->name('custom-orders.reject');
    Route::get('/custom-orders/{customOrderRequest}/create-order', [AdminCustomOrderController::class, 'createOrder'])->name('custom-orders.create-order');
    Route::post('/custom-orders/{customOrderRequest}/create-order', [AdminCustomOrderController::class, 'storeOrder'])->name('custom-orders.create-order.store');
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
    Route::patch('/users/{user}/toggle', [AdminUserController::class, 'toggleStatus'])->name('users.toggle');
    Route::resource('vouchers', AdminVoucherController::class);
});

/*
|--------------------------------------------------------------------------
| TRANG CHÍNH SÁCH PHÁP LÝ TMĐT (BẮT BUỘC)
|--------------------------------------------------------------------------
*/
Route::get('/dieu-khoan-giao-dich', [PolicyController::class, 'terms'])->name('policies.terms');
Route::get('/chinh-sach-doi-tra', [PolicyController::class, 'returns'])->name('policies.returns');
Route::get('/chinh-sach-bao-mat', [PolicyController::class, 'privacy'])->name('policies.privacy');
Route::get('/sitemap.xml', [SitemapController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');

require __DIR__ . '/auth.php';
require __DIR__ . '/finance.php';
