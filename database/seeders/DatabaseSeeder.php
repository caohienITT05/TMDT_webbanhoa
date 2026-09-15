<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Category;
use App\Models\Product;
use App\Models\DeliverySlot;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Tài khoản Quản trị viên (Admin)
        User::updateOrCreate(
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

        // Tài khoản Khách hàng mẫu (Customer)
        User::updateOrCreate(
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

        Category::firstOrCreate(['slug' => 'hoa-tuoi'], ['name' => 'Hoa tươi']);
        Category::firstOrCreate(['slug' => 'hoa-khai-truong'], ['name' => 'Hoa khai trương']);
        Category::firstOrCreate(['slug' => 'hoa-sinh-nhat'], ['name' => 'Hoa sinh nhật', 'is_seasonal' => false]);
        Category::firstOrCreate(['slug' => 'qua-tang-kem'], ['name' => 'Quà tặng kèm gấu bông']);
        Category::firstOrCreate(['slug' => 'hoa-tet-2026'], ['name' => 'Hoa tết sự kiện', 'is_seasonal' => true]);

        // Thêm vào cuối hàm run()
        $catHoaTuoi = \App\Models\Category::firstOrCreate(['slug' => 'hoa-tuoi'], ['name' => 'Hoa tươi']);
        $catKhaiTruong = \App\Models\Category::firstOrCreate(['slug' => 'hoa-khai-truong'], ['name' => 'Hoa khai trương']);
        $catSinhNhat = \App\Models\Category::firstOrCreate(['slug' => 'hoa-sinh-nhat'], ['name' => 'Hoa sinh nhật']);

        Product::updateOrCreate(
            ['slug' => 'bo-hoa-hong-do-ecua-dor'],
            [
                'category_id' => $catHoaTuoi->id,
                'name' => 'Bó hoa hồng đỏ Ecuador tình yêu',
                'description' => '99 bông hồng đỏ nhập khẩu tượng trưng cho tình yêu vĩnh cửu, gói giấy nhung cao cấp.',
                'price' => 750000,
                'sale_price' => 699000,
                'stock' => 25,
                'is_active' => true,
            ]
        );

        Product::updateOrCreate(
            ['slug' => 'ke-hoa-khai-truong-phat-tai'],
            [
                'category_id' => $catKhaiTruong->id,
                'name' => 'Kệ hoa khai trương Hồng Phát',
                'description' => 'Kệ hoa 2 tầng kết hợp hoa đồng tiền vàng, lan vũ nữ mang lại may mắn và tài lộc.',
                'price' => 1200000,
                'sale_price' => null,
                'stock' => 15,
                'is_active' => true,
            ]
        );

        Product::updateOrCreate(
            ['slug' => 'gio-hoa-huong-duong-nang-som'],
            [
                'category_id' => $catSinhNhat->id,
                'name' => 'Giỏ hoa hướng dương Nắng Sớm',
                'description' => 'Giỏ mây mộc mạc đan xen hướng dương rực rỡ và hoa baby trắng tinh khôi.',
                'price' => 450000,
                'sale_price' => 390000,
                'stock' => 30,
                'is_active' => true,
            ]
        );
        // 1. Tạo các khung giờ hẹn giao hoa
$slot1 = DeliverySlot::firstOrCreate(['time_range' => '08:00 - 10:00 (Sáng sớm)'], ['max_orders' => 20]);
$slot2 = DeliverySlot::firstOrCreate(['time_range' => '10:00 - 12:00 (Trưa)'], ['max_orders' => 20]);
$slot3 = DeliverySlot::firstOrCreate(['time_range' => '14:00 - 17:00 (Chiều)'], ['max_orders' => 25]);
$slot4 = DeliverySlot::firstOrCreate(['time_range' => '18:00 - 20:00 (Tối tiệc)'], ['max_orders' => 15]);

// 2. Tạo đơn hàng hoa mẫu kèm thông điệp thiệp
$customer = User::where('role', 'customer')->first();
$product1 = Product::first();

if ($customer && $product1) {
    $order = Order::updateOrCreate(
        ['recipient_phone' => '0912345678'],
        [
            'user_id' => $customer->id,
            'delivery_slot_id' => $slot3->id,
            'recipient_name' => 'Nguyễn Thị Thu Hà',
            'recipient_phone' => '0912345678',
            'recipient_address' => 'Phòng 402, Tòa nhà Landmark, Ba Đình, Hà Nội',
            'delivery_date' => now()->addDay(),
            'card_message' => 'Chúc mừng sinh nhật em gái yêu quý! Chúc em luôn xinh đẹp, rạng rỡ như những đóa hoa này.',
            'order_note' => 'Giao hàng đúng giờ, gọi trước 15 phút, không để hoa dập nát.',
            'subtotal' => $product1->price,
            'discount_amount' => 0,
            'total_amount' => $product1->price,
            'status' => 'CONFIRMED',
        ]
    );

    OrderItem::updateOrCreate(
        ['order_id' => $order->id, 'product_id' => $product1->id],
        [
            'quantity' => 1,
            'price' => $product1->price,
            'total' => $product1->price,
        ]
    );

    Payment::updateOrCreate(
        ['order_id' => $order->id],
        [
            'payment_method' => 'COD',
            'amount' => $product1->price,
            'status' => 'PENDING',
        ]
    );
}
    }
}
