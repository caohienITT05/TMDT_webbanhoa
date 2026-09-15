<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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
    }
}