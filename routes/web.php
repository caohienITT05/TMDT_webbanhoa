<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| CUSTOMER CONTROLLERS
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\CartController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\GiftController;
use App\Http\Controllers\ShippingController;

use App\Http\Controllers\Customer\ProductController;
use App\Http\Controllers\Customer\FavoriteController;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PayPalController;

/*
|--------------------------------------------------------------------------
| ADMIN CONTROLLERS
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ShippingMethodController;
use App\Http\Controllers\Admin\VoucherController as AdminVoucherController;
use App\Http\Controllers\Admin\GiftCardController;
use App\Http\Controllers\Admin\GiftWrapController;

/*
|--------------------------------------------------------------------------
| MODELS
|--------------------------------------------------------------------------
*/

use App\Models\Order;
use App\Models\Product;
use App\Models\User;


/*
|--------------------------------------------------------------------------
| CUSTOMER - HOME
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('customer.home');
})->name('home');

Route::get('/dat-hoa-theo-yeu-cau', function () {
    return view('customer.custom-order');
})->name('custom.order');


/*
|--------------------------------------------------------------------------
| CUSTOMER - CATEGORIES
|--------------------------------------------------------------------------
*/

Route::get('/danh-muc', function () {
    return view('customer.categories');
})->name('categories');


/*
|--------------------------------------------------------------------------
| CUSTOMER - FAVORITES
|--------------------------------------------------------------------------
*/

Route::get('/yeu-thich', [FavoriteController::class, 'index'])
    ->name('favorites.index');

Route::post('/yeu-thich/{id}', [FavoriteController::class, 'add'])
    ->name('favorites.add');

Route::delete('/yeu-thich/{id}', [FavoriteController::class, 'remove'])
    ->name('favorites.remove');


/*
|--------------------------------------------------------------------------
| CUSTOMER - PRODUCTS
|--------------------------------------------------------------------------
*/

Route::get('/san-pham', [ProductController::class, 'index'])
    ->name('products');

Route::get('/san-pham/{id}', [ProductController::class, 'show'])
    ->name('product.detail');


/*
|--------------------------------------------------------------------------
| CUSTOMER - CART
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
| GIFT CARD + GIFT WRAP
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
| SHIPPING
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
| DASHBOARD
|--------------------------------------------------------------------------
*/

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


/*
|--------------------------------------------------------------------------
| PROFILE
|--------------------------------------------------------------------------
*/

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
| CHECKOUT / ORDERS / PAYPAL
|--------------------------------------------------------------------------
|
| CheckoutController của TV2/TV4 được sửa để lấy
| dữ liệu CartItem của TV3.
|
*/

Route::middleware('auth')->group(function () {

    Route::get('/checkout', [CheckoutController::class, 'index'])
        ->name('checkout');

    Route::post('/checkout', [CheckoutController::class, 'store'])
        ->name('checkout.store');


    /*
    |--------------------------------------------------------------------------
    | CUSTOMER ORDERS
    |--------------------------------------------------------------------------
    */

    Route::get('/orders', [OrderController::class, 'index'])
        ->name('orders.index');

    Route::get('/orders/{order}', [OrderController::class, 'show'])
        ->name('orders.show');

    Route::patch('/orders/{order}/cancel', [OrderController::class, 'cancel'])
        ->name('orders.cancel');


    /*
    |--------------------------------------------------------------------------
    | PAYPAL
    |--------------------------------------------------------------------------
    */

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

        /*
        |--------------------------------------------------------------------------
        | ADMIN DASHBOARD
        |--------------------------------------------------------------------------
        */

        Route::get('/dashboard', function () {

            $totalRevenue = Order::where(
                'order_status',
                'completed'
            )->sum('total');

            $totalOrders = Order::count();

            $pendingOrders = Order::whereIn(
                'order_status',
                [
                    'pending',
                    'confirmed',
                    'processing'
                ]
            )->count();

            $totalProducts = Product::count();

            $totalCustomers = User::where(
                'role',
                'customer'
            )->count();

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


        /*
        |--------------------------------------------------------------------------
        | ADMIN - CATEGORIES
        |--------------------------------------------------------------------------
        */

        Route::resource(
            'categories',
            CategoryController::class
        );


        /*
        |--------------------------------------------------------------------------
        | ADMIN - PRODUCTS
        |--------------------------------------------------------------------------
        */

        Route::resource(
            'products',
            AdminProductController::class
        );


        /*
        |--------------------------------------------------------------------------
        | ADMIN - SHIPPING METHODS
        |--------------------------------------------------------------------------
        */

        Route::resource(
            'shipping-methods',
            ShippingMethodController::class
        );


        /*
        |--------------------------------------------------------------------------
        | ADMIN - VOUCHERS
        |--------------------------------------------------------------------------
        */

        Route::resource(
            'vouchers',
            AdminVoucherController::class
        );


        /*
        |--------------------------------------------------------------------------
        | ADMIN - GIFT CARDS
        |--------------------------------------------------------------------------
        */

        Route::resource(
            'gift-cards',
            GiftCardController::class
        );


        /*
        |--------------------------------------------------------------------------
        | ADMIN - GIFT WRAPS
        |--------------------------------------------------------------------------
        */

        Route::resource(
            'gift-wraps',
            GiftWrapController::class
        );


        /*
        |--------------------------------------------------------------------------
        | ADMIN - ORDERS
        |--------------------------------------------------------------------------
        */

        Route::get('/orders', [
            AdminOrderController::class,
            'index'
        ])->name('orders.index');

        Route::get('/orders/{order}', [
            AdminOrderController::class,
            'show'
        ])->name('orders.show');

        Route::patch('/orders/{order}/status', [
            AdminOrderController::class,
            'updateStatus'
        ])->name('orders.update-status');


        /*
        |--------------------------------------------------------------------------
        | ADMIN - USERS
        |--------------------------------------------------------------------------
        */

        Route::get('/users', [
            UserController::class,
            'index'
        ])->name('users.index');

        Route::get('/users/{user}', [
            UserController::class,
            'show'
        ])->name('users.show');

        Route::patch('/users/{user}/toggle', [
            UserController::class,
            'toggleStatus'
        ])->name('users.toggle');

    });


/*
|--------------------------------------------------------------------------
| AUTHENTICATION
|--------------------------------------------------------------------------
*/

require __DIR__ . '/auth.php';