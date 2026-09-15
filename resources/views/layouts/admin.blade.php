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
            <div class="p-5 text-xl font-bold border-b border-slate-800 text-rose-400 flex items-center gap-2">
                🌸 BloomGift Admin
            </div>
            <nav class="flex-1 p-4 space-y-1 text-sm font-medium">
                <a href="{{ route('admin.dashboard') }}"
                    class="flex items-center px-4 py-2.5 rounded-lg hover:bg-slate-800 {{ request()->routeIs('admin.dashboard') ? 'bg-slate-800 text-rose-400' : '' }}">
                    📊 Bảng điều khiển
                </a>
                <a href="{{ route('admin.categories.index') }}"
                    class="flex items-center px-4 py-2.5 rounded-lg hover:bg-slate-800 {{ request()->routeIs('admin.categories.*') ? 'bg-slate-800 text-rose-400' : '' }}">
                    🏷️ Quản lý danh mục
                </a>
                <a href="{{ route('admin.users.index') }}"
                    class="flex items-center px-4 py-2.5 rounded-lg hover:bg-slate-800 {{ request()->routeIs('admin.users.*') ? 'bg-slate-800 text-rose-400' : '' }}">
                    👥 Quản lý người dùng
                </a>
            </nav>
            <a href="{{ route('admin.products.index') }}"
                class="flex items-center px-4 py-2.5 rounded-lg hover:bg-slate-800 {{ request()->routeIs('admin.products.*') ? 'bg-slate-800 text-rose-400 font-bold' : '' }}">
                💐 Quản lý sản phẩm
                <a href="{{ route('admin.orders.index') }}"
                    class="flex items-center px-4 py-2.5 rounded-lg hover:bg-slate-800 {{ request()->routeIs('admin.orders.*') ? 'bg-slate-800 text-rose-400 font-bold' : '' }}">
                    📦 Quản lý đơn hàng
                </a>
            </a>
            <div class="p-4 border-t border-slate-800">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="w-full text-left px-4 py-2 text-sm text-red-400 hover:bg-slate-800 rounded-lg">
                        🚪 Đăng xuất
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col">
            <header class="bg-white shadow-sm h-16 flex items-center justify-between px-8 border-b">
                <h1 class="text-lg font-semibold text-gray-800">@yield('title', 'Quản trị')</h1>
                <div class="text-sm text-gray-600">
                    Xin chào, <span class="font-bold text-gray-900">{{ Auth::user()->name }}</span>
                </div>
            </header>

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