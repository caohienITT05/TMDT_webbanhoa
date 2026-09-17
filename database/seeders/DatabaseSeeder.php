<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\DeliverySlot;
use App\Models\GiftCard;
use App\Models\GiftWrap;
use App\Models\Product;
use App\Models\User;
use App\Models\Voucher;
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

        User::updateOrCreate(
            ['email' => 'admin@bloomgift.com'],
            [
                'name' => 'BloomGift Admin',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'phone' => '0987654321',
                'address' => 'Ha Noi, Viet Nam',
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'customer@bloomgift.com'],
            [
                'name' => 'Khach Hang Mau',
                'password' => Hash::make('password123'),
                'role' => 'customer',
                'phone' => '0912345678',
                'address' => 'Ha Dong, Ha Noi',
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
                'name' => 'Hoa tuoi',
                'is_seasonal' => false,
            ]
        );

        $catKhaiTruong = Category::updateOrCreate(
            ['slug' => 'hoa-khai-truong'],
            [
                'name' => 'Hoa khai truong',
                'is_seasonal' => false,
            ]
        );

        $catSinhNhat = Category::updateOrCreate(
            ['slug' => 'hoa-sinh-nhat'],
            [
                'name' => 'Hoa sinh nhat',
                'is_seasonal' => false,
            ]
        );

        Category::updateOrCreate(
            ['slug' => 'qua-tang-kem'],
            [
                'name' => 'Qua tang kem gau bong',
                'is_seasonal' => false,
            ]
        );

        Category::updateOrCreate(
            ['slug' => 'hoa-tet-2026'],
            [
                'name' => 'Hoa Tet su kien',
                'is_seasonal' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | PRODUCTS
        |--------------------------------------------------------------------------
        */

        Product::updateOrCreate(
            ['slug' => 'bo-hoa-hong-do-ecua-dor'],
            [
                'category_id' => $catHoaTuoi->id,
                'name' => 'Bo hoa hong do Ecuador tinh yeu',
                'description' => 'Bo hoa hong do nhap khau cao cap.',
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
                'name' => 'Ke hoa khai truong Hong Phat',
                'description' => 'Ke hoa khai truong hai tang.',
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
                'name' => 'Gio hoa huong duong Nang Som',
                'description' => 'Gio hoa huong duong ket hop hoa baby.',
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
        |
        | Current migration uses:
        | id, time_range, max_orders, is_active
        |
        */

        DeliverySlot::updateOrCreate(
            ['time_range' => '08:00 - 10:00'],
            [
                'max_orders' => 20,
                'is_active' => true,
            ]
        );

        DeliverySlot::updateOrCreate(
            ['time_range' => '14:00 - 16:00'],
            [
                'max_orders' => 20,
                'is_active' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | GIFT CARDS
        |--------------------------------------------------------------------------
        */

        GiftCard::updateOrCreate(
            ['name' => 'Thiep chuc mung'],
            [
                'price' => 10000,
                'image' => null,
                'is_active' => true,
            ]
        );

        GiftCard::updateOrCreate(
            ['name' => 'Thiep sinh nhat'],
            [
                'price' => 15000,
                'image' => null,
                'is_active' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | GIFT WRAPS
        |--------------------------------------------------------------------------
        */

        GiftWrap::updateOrCreate(
            ['name' => 'Goi qua co ban'],
            [
                'price' => 20000,
                'image' => null,
                'is_active' => true,
            ]
        );

        GiftWrap::updateOrCreate(
            ['name' => 'Goi qua cao cap'],
            [
                'price' => 30000,
                'image' => null,
                'is_active' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | VOUCHERS
        |--------------------------------------------------------------------------
        |
        | Current TV3 vouchers schema:
        | code, type, value, min_order_amount, max_discount,
        | starts_at, expires_at, usage_limit, used_count, is_active
        |
        */

        Voucher::updateOrCreate(
            ['code' => 'WELCOME50'],
            [
                'type' => 'fixed',
                'value' => 50000,
                'min_order_amount' => 300000,
                'max_discount' => null,
                'starts_at' => now(),
                'expires_at' => now()->addYear(),
                'usage_limit' => 100,
                'used_count' => 0,
                'is_active' => true,
            ]
        );

        Voucher::updateOrCreate(
            ['code' => 'SALE10'],
            [
                'type' => 'percent',
                'value' => 10,
                'min_order_amount' => 500000,
                'max_discount' => 100000,
                'starts_at' => now(),
                'expires_at' => now()->addYear(),
                'usage_limit' => 100,
                'used_count' => 0,
                'is_active' => true,
            ]
        );
    }
}