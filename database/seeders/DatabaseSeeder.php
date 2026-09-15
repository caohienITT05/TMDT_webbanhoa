<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Category;
use App\Models\Product;

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
    }
}