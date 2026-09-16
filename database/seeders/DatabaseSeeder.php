<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\DeliverySlot;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | USERS
        |--------------------------------------------------------------------------
        */

        $admin = User::updateOrCreate(
            ['email' => 'admin@bloomgift.com'],
            [
                'name' => 'BloomGift Admin',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'phone' => '0987654321',
                'address' => 'Hà Nội, Việt Nam',
                'is_active' => true,
            ]
        );

        $customer = User::updateOrCreate(
            ['email' => 'customer@bloomgift.com'],
            [
                'name' => 'Khách Hàng Mẫu',
                'password' => Hash::make('password123'),
                'role' => 'customer',
                'phone' => '0912345678',
                'address' => 'Hà Đông, Hà Nội',
                'is_active' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | CATEGORIES
        |--------------------------------------------------------------------------
        */

        $catHoaTuoi = Category::updateOrCreate(
            ['slug' => 'hoa-tuoi'],
            [
                'name' => 'Hoa tươi',
                'is_seasonal' => false,
            ]
        );

        $catKhaiTruong = Category::updateOrCreate(
            ['slug' => 'hoa-khai-truong'],
            [
                'name' => 'Hoa khai trương',
                'is_seasonal' => false,
            ]
        );

        $catSinhNhat = Category::updateOrCreate(
            ['slug' => 'hoa-sinh-nhat'],
            [
                'name' => 'Hoa sinh nhật',
                'is_seasonal' => false,
            ]
        );

        Category::updateOrCreate(
            ['slug' => 'qua-tang-kem'],
            [
                'name' => 'Quà tặng kèm gấu bông',
                'is_seasonal' => false,
            ]
        );

        Category::updateOrCreate(
            ['slug' => 'hoa-tet-2026'],
            [
                'name' => 'Hoa Tết sự kiện',
                'is_seasonal' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | PRODUCTS
        |--------------------------------------------------------------------------
        */

        $product1 = Product::updateOrCreate(
            ['slug' => 'bo-hoa-hong-do-ecua-dor'],
            [
                'category_id' => $catHoaTuoi->id,
                'name' => 'Bó hoa hồng đỏ Ecuador tình yêu',
                'description' => 'Bó hoa hồng đỏ nhập khẩu cao cấp.',
                'price' => 750000,
                'sale_price' => 699000,
                'stock' => 25,
                'image' => null,
                'is_active' => true,
            ]
        );

        Product::updateOrCreate(
            ['slug' => 'ke-hoa-khai-truong-phat-tai'],
            [
                'category_id' => $catKhaiTruong->id,
                'name' => 'Kệ hoa khai trương Hồng Phát',
                'description' => 'Kệ hoa khai trương hai tầng.',
                'price' => 1200000,
                'sale_price' => null,
                'stock' => 15,
                'image' => null,
                'is_active' => true,
            ]
        );

        Product::updateOrCreate(
            ['slug' => 'gio-hoa-huong-duong-nang-som'],
            [
                'category_id' => $catSinhNhat->id,
                'name' => 'Giỏ hoa hướng dương Nắng Sớm',
                'description' => 'Giỏ hoa hướng dương kết hợp hoa baby.',
                'price' => 450000,
                'sale_price' => 390000,
                'stock' => 30,
                'image' => null,
                'is_active' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | DELIVERY SLOTS
        |--------------------------------------------------------------------------
        */

        $slotMorning = DeliverySlot::updateOrCreate(
            ['name' => '08:00 - 10:00'],
            [
                'start_time' => '08:00',
                'end_time' => '10:00',
                'max_orders' => 20,
                'is_active' => true,
            ]
        );

        $slotAfternoon = DeliverySlot::updateOrCreate(
            ['name' => '14:00 - 16:00'],
            [
                'start_time' => '14:00',
                'end_time' => '16:00',
                'max_orders' => 20,
                'is_active' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | SAMPLE ORDER
        |--------------------------------------------------------------------------
        */

        $order = Order::updateOrCreate(
            ['order_code' => 'BG-DEMO-0001'],
            [
                'recipient_name' => 'Nguyễn Thị Thu Hà',
                'recipient_phone' => $customer->phone,
                'recipient_address' => $customer->address,
                'delivery_date' => now()->addDay(),
                'delivery_slot_id' => $slotAfternoon->id,

                'subtotal' => $product1->price,
                'discount' => 0,
                'shipping_fee' => 30000,
                'total' => $product1->price + 30000,

                'payment_method' => 'cod',
                'payment_status' => 'pending',
                'order_status' => 'confirmed',

                'note' => 'Đơn hàng mẫu của nhóm.',
            ]
        );

        OrderItem::updateOrCreate(
            [
                'order_id' => $order->id,
                'product_id' => $product1->id,
            ],
            [
                'product_name' => $product1->name,
                'price' => $product1->price,
                'quantity' => 1,
                'subtotal' => $product1->price,
            ]
        );

        Payment::updateOrCreate(
            ['order_id' => $order->id],
            [
                'payment_method' => 'cod',
                'transaction_id' => null,
                'amount' => $order->total,
                'status' => 'pending',
                'paid_at' => null,
            ]
        );
    }
}