<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'BloomGift') }} - Admin Portal</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100 font-sans antialiased">

    <div class="min-h-screen flex">

        <!-- Sidebar -->
        <aside class="w-64 bg-slate-900 text-slate-100 flex flex-col shrink-0">

            <!-- Logo -->
            <div class="p-5 text-xl font-bold border-b border-slate-800 text-rose-400 flex items-center gap-2">
                🌸 BloomGift Admin
            </div>

            <!-- Menu -->
            <nav class="flex-1 p-4 space-y-1 text-sm font-medium">

                <!-- Dashboard -->
                <a href="{{ route('admin.dashboard') }}"
                    class="flex items-center px-4 py-2.5 rounded-lg hover:bg-slate-800
                    {{ request()->routeIs('admin.dashboard') ? 'bg-slate-800 text-rose-400' : '' }}">
                    📊 Bảng điều khiển
                </a>

                <!-- Categories -->
                <a href="{{ route('admin.categories.index') }}"
                    class="flex items-center px-4 py-2.5 rounded-lg hover:bg-slate-800
                    {{ request()->routeIs('admin.categories.*') ? 'bg-slate-800 text-rose-400' : '' }}">
                    🏷️ Quản lý danh mục
                </a>

                <!-- Products -->
                <a href="{{ route('admin.products.index') }}"
                    class="flex items-center px-4 py-2.5 rounded-lg hover:bg-slate-800
                    {{ request()->routeIs('admin.products.*') ? 'bg-slate-800 text-rose-400' : '' }}">
                    💐 Quản lý sản phẩm
                </a>

                <!-- Orders -->
                <a href="{{ route('admin.orders.index') }}"
                    class="flex items-center px-4 py-2.5 rounded-lg hover:bg-slate-800
                    {{ request()->routeIs('admin.orders.*') ? 'bg-slate-800 text-rose-400' : '' }}">
                    📦 Quản lý đơn hàng
                </a>

                <!-- Users -->
                <a href="{{ route('admin.users.index') }}"
                    class="flex items-center px-4 py-2.5 rounded-lg hover:bg-slate-800
                    {{ request()->routeIs('admin.users.*') ? 'bg-slate-800 text-rose-400' : '' }}">
                    👥 Quản lý người dùng
                </a>

                <!-- Divider -->
                <div class="pt-4 pb-2">
                    <div class="px-4 text-xs font-semibold text-slate-500 uppercase">
                        Cấu hình bán hàng
                    </div>
                </div>

                <!-- Vouchers -->
                <a href="{{ route('admin.vouchers.index') }}"
                    class="flex items-center px-4 py-2.5 rounded-lg hover:bg-slate-800
                    {{ request()->routeIs('admin.vouchers.*') ? 'bg-slate-800 text-rose-400' : '' }}">
                    🎟️ Quản lý Voucher
                </a>

                <!-- Gift Cards -->
                <a href="{{ route('admin.gift-cards.index') }}"
                    class="flex items-center px-4 py-2.5 rounded-lg hover:bg-slate-800
                    {{ request()->routeIs('admin.gift-cards.*') ? 'bg-slate-800 text-rose-400' : '' }}">
                    💌 Quản lý thiệp
                </a>

                <!-- Gift Wraps -->
                <a href="{{ route('admin.gift-wraps.index') }}"
                    class="flex items-center px-4 py-2.5 rounded-lg hover:bg-slate-800
                    {{ request()->routeIs('admin.gift-wraps.*') ? 'bg-slate-800 text-rose-400' : '' }}">
                    🎁 Quản lý gói quà
                </a>

                <!-- Shipping Methods -->
                <a href="{{ route('admin.shipping-methods.index') }}"
                    class="flex items-center px-4 py-2.5 rounded-lg hover:bg-slate-800
                    {{ request()->routeIs('admin.shipping-methods.*') ? 'bg-slate-800 text-rose-400' : '' }}">
                    🚚 Phương thức giao hàng
                </a>

            </nav>

            <!-- Logout -->
            <div class="p-4 border-t border-slate-800">

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button
                        type="submit"
                        class="w-full text-left px-4 py-2 text-sm text-red-400 hover:bg-slate-800 rounded-lg">
                        🚪 Đăng xuất
                    </button>
                </form>

            </div>

        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col">

            <!-- Header -->
            <header class="bg-white shadow-sm h-16 flex items-center justify-between px-8 border-b">

                <h1 class="text-lg font-semibold text-gray-800">
                    @yield('title', 'Quản trị')
                </h1>

                <div class="text-sm text-gray-600">
                    Xin chào,
                    <span class="font-bold text-gray-900">
                        {{ Auth::user()->name }}
                    </span>
                </div>

            </header>

            <!-- Main -->
            <main class="p-8 flex-1">

                @if(session('success'))
                    <div class="mb-4 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 text-sm rounded shadow-sm">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm rounded shadow-sm">
                        {{ session('error') }}
                    </div>
                @endif

                @yield('content')

            </main>

        </div>

    </div>

</body>

</html>