<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'BloomGift') }} - Quản Trị Cửa Hàng Hoa</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-[#fff5f7] font-sans antialiased text-gray-700">
    <div class="min-h-screen flex">
        <!-- Sidebar Hồng Pastel -->
        <aside class="w-64 bg-white border-r border-rose-100 flex flex-col justify-between shrink-0 shadow-sm relative">
            <div>
                <!-- Logo BloomGift -->
                <div class="h-16 flex items-center px-6 gap-2 border-b border-rose-100/70">
                    <span class="text-2xl">🌸</span>
                    <span class="font-bold text-lg text-rose-500 tracking-wide">BloomGift Admin</span>
                </div>

                <!-- Navigation Menu -->
                <nav class="p-4 space-y-1.5 text-sm font-medium">
                    <a href="{{ route('admin.dashboard') }}"
                        class="flex items-center gap-3 px-4 py-2.5 rounded-full transition {{ request()->routeIs('admin.dashboard') ? 'bg-[#ffe2e8] text-rose-600 font-bold shadow-sm' : 'text-gray-600 hover:bg-rose-50 hover:text-rose-500' }}">
                        <span>📊</span> Bảng điều khiển
                    </a>
                    <a href="{{ route('admin.categories.index') }}"
                        class="flex items-center gap-3 px-4 py-2.5 rounded-full transition {{ request()->routeIs('admin.categories.*') ? 'bg-[#ffe2e8] text-rose-600 font-bold shadow-sm' : 'text-gray-600 hover:bg-rose-50 hover:text-rose-500' }}">
                        <span>🏷️</span> Quản lý danh mục
                    </a>
                    <a href="{{ route('admin.users.index') }}"
                        class="flex items-center gap-3 px-4 py-2.5 rounded-full transition {{ request()->routeIs('admin.users.*') ? 'bg-[#ffe2e8] text-rose-600 font-bold shadow-sm' : 'text-gray-600 hover:bg-rose-50 hover:text-rose-500' }}">
                        <span>👥</span> Quản lý người dùng
                    </a>
                    <a href="{{ route('admin.products.index') }}"
                        class="flex items-center gap-3 px-4 py-2.5 rounded-full transition {{ request()->routeIs('admin.products.*') ? 'bg-[#ffe2e8] text-rose-600 font-bold shadow-sm' : 'text-gray-600 hover:bg-rose-50 hover:text-rose-500' }}">
                        <span>💐</span> Quản lý sản phẩm
                    </a>
                    <a href="{{ route('admin.orders.index') }}"
                        class="flex items-center gap-3 px-4 py-2.5 rounded-full transition {{ request()->routeIs('admin.orders.*') ? 'bg-[#ffe2e8] text-rose-600 font-bold shadow-sm' : 'text-gray-600 hover:bg-rose-50 hover:text-rose-500' }}">
                        <span>🛒</span> Quản lý đơn hàng
                    </a>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.vouchers.*') ? 'active' : '' }}"
                            href="{{ route('admin.vouchers.index') }}">
                            <i class="bi bi-ticket-perforated me-2"></i>
                            <span>Quản lý Voucher & Khuyến mại</span>
                        </a>
                    </li>
                    <!-- Quản lý khung giờ giao hoa -->
                    <a href="{{ route('admin.delivery-slots.index') }}"
                        class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition {{ request()->routeIs('admin.delivery-slots.*') ? 'bg-rose-100 text-rose-700 font-bold' : 'text-gray-600 hover:bg-rose-50 hover:text-rose-600' }}">
                        <span>⏰</span>
                        <span>Quản lý khung giờ</span>
                    </a>
                </nav>
            </div>

            <!-- Nút Đăng xuất & Bó hoa trang trí góc dưới -->
            <div class="p-4 relative">
                <div class="absolute right-2 bottom-12 opacity-80 pointer-events-none text-4xl select-none">
                    🌷💐
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="flex items-center gap-2 px-4 py-2 text-xs font-semibold text-rose-400 hover:text-rose-600 transition">
                        <span>🚪</span> Đăng xuất
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col">
            <!-- Header chuẩn phong cách BloomGift -->
            <header
                class="bg-white/80 backdrop-blur h-16 flex items-center justify-between px-8 border-b border-rose-100 shadow-sm sticky top-0 z-10">
                <div class="flex items-center gap-4">
                    <button class="text-rose-400 hover:text-rose-600 text-lg">☰</button>
                    <span
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-rose-50 text-rose-500 text-xs font-medium border border-rose-100">
                        🌸 Chào mừng bạn đến với BloomGift Admin
                    </span>
                </div>
                <div class="flex items-center gap-4 text-xs">
                    <button class="text-gray-400 hover:text-rose-500 relative">
                        🔔
                        <span class="w-1.5 h-1.5 bg-rose-500 rounded-full absolute -top-0.5 -right-0.5"></span>
                    </button>
                    <div class="flex items-center gap-2">
                        <span
                            class="w-7 h-7 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center font-bold text-xs">
                            🌸
                        </span>
                        <span class="text-gray-600">Xin chào, <b
                                class="text-gray-800">{{ Auth::user()->name }}</b></span>
                    </div>
                </div>
            </header>

            <!-- Nội dung chính -->
            <main class="p-8 flex-1 space-y-6">
                @if(session('success'))
                    <div
                        class="p-3.5 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-xl shadow-sm flex items-center gap-2">
                        <span>✨</span> {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div
                        class="p-3.5 bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl shadow-sm flex items-center gap-2">
                        <span>⚠️</span> {{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>

</html>