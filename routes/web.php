<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\VoucherController as AdminVoucherController;
use App\Http\Controllers\PolicyController;

// 1. Controllers Quản trị (TV1 & TV4)
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\DeliverySlotController as AdminDeliverySlotController;

// 2. Controllers Giỏ hàng, Quà tặng & Voucher (TV3)
use App\Http\Controllers\CartController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\GiftController;
use App\Http\Controllers\ShippingController;

// 3. Controllers Đặt hàng & Thanh toán PayPal (TV4)
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Customer\OrderController as CustomerOrderController;

// Models
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
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

// Danh sách hoa & Tìm kiếm
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

Route::post('/voucher/apply', [VoucherController::class, 'apply'])->name('voucher.apply');
Route::any('/voucher/remove', [VoucherController::class, 'remove'])->name('voucher.remove');
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

Route::get('/fix-db', function () {
    Schema::table('delivery_slots', function (Blueprint $table) {
        if (!Schema::hasColumn('delivery_slots', 'name')) {
            $table->string('name')->nullable()->after('id');
        }
        if (!Schema::hasColumn('delivery_slots', 'is_active')) {
            $table->boolean('is_active')->default(true);
        }
        if (!Schema::hasColumn('delivery_slots', 'start_time')) {
            $table->time('start_time')->nullable();
        }
        if (!Schema::hasColumn('delivery_slots', 'end_time')) {
            $table->time('end_time')->nullable();
        }
        if (!Schema::hasColumn('delivery_slots', 'max_orders')) {
            $table->integer('max_orders')->default(20);
        }
    });

    // 1. Thêm toàn bộ các cột thanh toán còn thiếu vào bảng orders
    Schema::table('orders', function (Blueprint $table) {
        if (!Schema::hasColumn('orders', 'order_code'))
            $table->string('order_code')->nullable()->after('id');
        if (!Schema::hasColumn('orders', 'recipient_name'))
            $table->string('recipient_name')->nullable();
        if (!Schema::hasColumn('orders', 'recipient_phone'))
            $table->string('recipient_phone')->nullable();
        if (!Schema::hasColumn('orders', 'recipient_address'))
            $table->text('recipient_address')->nullable();
        if (!Schema::hasColumn('orders', 'delivery_date'))
            $table->date('delivery_date')->nullable();
        if (!Schema::hasColumn('orders', 'delivery_slot_id'))
            $table->unsignedBigInteger('delivery_slot_id')->nullable();
        if (!Schema::hasColumn('orders', 'subtotal'))
            $table->decimal('subtotal', 15, 2)->default(0);
        if (!Schema::hasColumn('orders', 'discount'))
            $table->decimal('discount', 15, 2)->default(0);
        if (!Schema::hasColumn('orders', 'shipping_fee'))
            $table->decimal('shipping_fee', 15, 2)->default(0);
        if (!Schema::hasColumn('orders', 'total'))
            $table->decimal('total', 15, 2)->default(0);
        if (!Schema::hasColumn('orders', 'payment_method'))
            $table->string('payment_method')->default('cod');
        if (!Schema::hasColumn('orders', 'payment_status'))
            $table->string('payment_status')->default('pending');
        if (!Schema::hasColumn('orders', 'order_status'))
            $table->string('order_status')->default('pending');
        if (!Schema::hasColumn('orders', 'note'))
            $table->text('note')->nullable();
    });

    // Cấu hình tương thích các cột cũ của TV1 để không bị lỗi bắt buộc nhập
    try {
        DB::statement("ALTER TABLE orders MODIFY status VARCHAR(50) DEFAULT 'PENDING' NULL");
        DB::statement("ALTER TABLE orders MODIFY total_amount DECIMAL(15,2) DEFAULT 0 NULL");
        DB::statement("ALTER TABLE orders MODIFY user_id BIGINT UNSIGNED NULL");
    } catch (\Throwable $e) {
    }

    // 2. Thêm các cột còn thiếu vào bảng chi tiết đơn hàng order_items
    Schema::table('order_items', function (Blueprint $table) {
        if (!Schema::hasColumn('order_items', 'product_name'))
            $table->string('product_name')->nullable()->after('product_id');
        if (!Schema::hasColumn('order_items', 'price'))
            $table->decimal('price', 15, 2)->default(0);
        if (!Schema::hasColumn('order_items', 'quantity'))
            $table->unsignedInteger('quantity')->default(1);
        if (!Schema::hasColumn('order_items', 'subtotal'))
            $table->decimal('subtotal', 15, 2)->default(0);
    });

    try {
        DB::statement("ALTER TABLE order_items MODIFY total DECIMAL(15,2) DEFAULT 0 NULL");
    } catch (\Throwable $e) {
    }

    // 3. Tạo bảng lưu giao dịch thanh toán payments nếu chưa có
    if (!Schema::hasTable('payments')) {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('payment_method')->default('paypal');
            $table->string('transaction_id')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('currency', 10)->default('VND');
            $table->string('status')->default('pending');
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    // 4. Đồng bộ bảng migrations để artisan migrate không bao giờ báo lỗi trùng bảng
    $migrations = [
        '2026_09_07_041814_create_delivery_slots_table',
        '2026_09_07_041815_create_orders_table',
        '2026_09_07_041816_create_order_items_table',
        '2026_09_07_041819_create_payments_table',
    ];
    $batch = (DB::table('migrations')->max('batch') ?? 0) + 1;
    foreach ($migrations as $m) {
        if (!DB::table('migrations')->where('migration', $m)->exists()) {
            DB::table('migrations')->insert(['migration' => $m, 'batch' => $batch]);
        }
    }

    return "<div style='font-family: sans-serif; text-align: center; margin-top: 60px;'>
        <h1 style='color: #16a34a;'>🎉 ĐÃ ĐỒNG BỘ CSDL THÀNH CÔNG 100%!</h1>
        <p style='color: #475569; font-size: 18px;'>Tất cả các cột checkout và thanh toán đã sẵn sàng.</p>
        <a href='/checkout' style='display: inline-block; padding: 10px 24px; background: #2563eb; color: #fff; text-decoration: none; border-radius: 6px; font-weight: bold;'>Quay lại trang Checkout để Đặt Hàng</a>
    </div>";
});

require __DIR__ . '/auth.php';
