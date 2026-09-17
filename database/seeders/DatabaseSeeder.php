<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use App\Models\DeliverySlot;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | 1. TÀI KHOẢN NGƯỜI DÙNG (USERS)
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
        | 2. DANH MỤC HOA & QUÀ TẶNG (CATEGORIES)
        |--------------------------------------------------------------------------
        */
        $catHoaTuoi = Category::updateOrCreate(
            ['slug' => 'hoa-tuoi'],
            ['name' => 'Hoa tươi', 'is_seasonal' => false]
        );

        $catKhaiTruong = Category::updateOrCreate(
            ['slug' => 'hoa-khai-truong'],
            ['name' => 'Hoa khai trương', 'is_seasonal' => false]
        );

        $catSinhNhat = Category::updateOrCreate(
            ['slug' => 'hoa-sinh-nhat'],
            ['name' => 'Hoa sinh nhật', 'is_seasonal' => false]
        );

        Category::updateOrCreate(
            ['slug' => 'qua-tang-kem'],
            ['name' => 'Quà tặng kèm gấu bông', 'is_seasonal' => false]
        );

        Category::updateOrCreate(
            ['slug' => 'hoa-tet-2026'],
            ['name' => 'Hoa Tết sự kiện', 'is_seasonal' => true]
        );

        /*
        |--------------------------------------------------------------------------
        | 3. SẢN PHẨM HOA (PRODUCTS)
        |--------------------------------------------------------------------------
        */
        $product1 = Product::updateOrCreate(
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

        /*
        |--------------------------------------------------------------------------
        | 4. KHUNG GIỜ GIAO HOA (DELIVERY SLOTS) - Tự động tương thích cả TV1 & TV4
        |--------------------------------------------------------------------------
        */
        $hasNameCol = Schema::hasColumn('delivery_slots', 'name');

        $slots = [
            ['range' => '08:00 - 10:00 (Sáng sớm)', 'name' => '08:00 - 10:00', 'start' => '08:00', 'end' => '10:00', 'max' => 20],
            ['range' => '10:00 - 12:00 (Trưa)',     'name' => '10:00 - 12:00', 'start' => '10:00', 'end' => '12:00', 'max' => 20],
            ['range' => '14:00 - 16:00 (Chiều)',    'name' => '14:00 - 16:00', 'start' => '14:00', 'end' => '16:00', 'max' => 25],
            ['range' => '18:00 - 20:00 (Tối tiệc)', 'name' => '18:00 - 20:00', 'start' => '18:00', 'end' => '20:00', 'max' => 15],
        ];

        $slotAfternoon = null;
        foreach ($slots as $item) {
            $slotData = [
                'time_range' => $item['range'],
                'max_orders' => $item['max'],
                'is_active' => true,
            ];
            if ($hasNameCol) {
                $slotData['name'] = $item['name'];
                $slotData['start_time'] = $item['start'];
                $slotData['end_time'] = $item['end'];
            }

            $searchCond = $hasNameCol ? ['name' => $item['name']] : ['time_range' => $item['range']];
            $createdSlot = DeliverySlot::updateOrCreate($searchCond, $slotData);

            if ($item['start'] === '14:00') {
                $slotAfternoon = $createdSlot;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 5. ĐƠN HÀNG MẪU (ORDER) - Tự động nhận diện cột của TV1 hoặc TV4
        |--------------------------------------------------------------------------
        */
        $shippingFee = 30000;
        $totalPrice = $product1->price + $shippingFee;

        $hasOrderCode = Schema::hasColumn('orders', 'order_code');
        $orderSearch = $hasOrderCode ? ['order_code' => 'BG-DEMO-0001'] : ['recipient_phone' => '0912345678'];

        $orderData = [
            'user_id' => $customer->id,
            'recipient_name' => 'Nguyễn Thị Thu Hà',
            'recipient_phone' => '0912345678',
            'recipient_address' => 'Phòng 402, Tòa nhà Landmark, Ba Đình, Hà Nội',
            'delivery_date' => now()->addDay(),
            'delivery_slot_id' => $slotAfternoon ? $slotAfternoon->id : null,
            'card_message' => 'Chúc mừng sinh nhật em gái yêu quý! Chúc em luôn rực rỡ như những đóa hoa.',
            'subtotal' => $product1->price,
        ];

        if ($hasOrderCode) $orderData['order_code'] = 'BG-DEMO-0001';
        if (Schema::hasColumn('orders', 'order_note')) $orderData['order_note'] = 'Đơn hàng mẫu của nhóm.';
        if (Schema::hasColumn('orders', 'note')) $orderData['note'] = 'Đơn hàng mẫu của nhóm.';
        if (Schema::hasColumn('orders', 'discount_amount')) $orderData['discount_amount'] = 0;
        if (Schema::hasColumn('orders', 'discount')) $orderData['discount'] = 0;
        if (Schema::hasColumn('orders', 'shipping_fee')) $orderData['shipping_fee'] = $shippingFee;
        if (Schema::hasColumn('orders', 'total_amount')) $orderData['total_amount'] = $totalPrice;
        if (Schema::hasColumn('orders', 'total')) $orderData['total'] = $totalPrice;
        if (Schema::hasColumn('orders', 'status')) $orderData['status'] = 'confirmed';
        if (Schema::hasColumn('orders', 'order_status')) $orderData['order_status'] = 'confirmed';
        if (Schema::hasColumn('orders', 'payment_method')) $orderData['payment_method'] = 'cod';
        if (Schema::hasColumn('orders', 'payment_status')) $orderData['payment_status'] = 'pending';

        $order = Order::updateOrCreate($orderSearch, $orderData);

        /*
        |--------------------------------------------------------------------------
        | 6. CHI TIẾT ĐƠN HÀNG (ORDER ITEMS)
        |--------------------------------------------------------------------------
        */
        $itemData = [
            'quantity' => 1,
            'price' => $product1->price,
        ];
        if (Schema::hasColumn('order_items', 'product_name')) $itemData['product_name'] = $product1->name;
        if (Schema::hasColumn('order_items', 'total')) $itemData['total'] = $product1->price;
        if (Schema::hasColumn('order_items', 'subtotal')) $itemData['subtotal'] = $product1->price;

        OrderItem::updateOrCreate(
            [
                'order_id' => $order->id,
                'product_id' => $product1->id,
            ],
            $itemData
        );

        /*
        |--------------------------------------------------------------------------
        | 7. THANH TOÁN (PAYMENTS)
        |--------------------------------------------------------------------------
        */
        $paymentData = [
            'payment_method' => 'cod',
            'amount' => $totalPrice,
            'status' => 'pending',
        ];
        if (Schema::hasColumn('payments', 'transaction_id')) $paymentData['transaction_id'] = null;
        if (Schema::hasColumn('payments', 'paid_at')) $paymentData['paid_at'] = null;

        Payment::updateOrCreate(
            ['order_id' => $order->id],
            $paymentData
        );
    }
}