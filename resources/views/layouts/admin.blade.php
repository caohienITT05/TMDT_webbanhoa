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
    <div x-data="{ menuOpen: false }" @keydown.escape.window="menuOpen = false" class="min-h-screen flex">
        <!-- Sidebar Hồng Pastel -->
        <!-- ========================================================
     SIDEBAR ADMIN - THEME ĐEN SANG TRỌNG & ICON VECTOR VECTOR
     ======================================================== -->
        <div x-cloak x-show="menuOpen" x-transition.opacity @click="menuOpen = false" class="fixed inset-0 z-30 bg-slate-950/45 lg:hidden"></div>
        <aside :class="{ 'translate-x-0': menuOpen }" class="fixed inset-y-0 left-0 z-40 flex min-h-screen w-64 shrink-0 -translate-x-full flex-col justify-between shadow-2xl transition-transform duration-200 lg:sticky lg:top-0 lg:translate-x-0 select-none"
            style="background: #14151f; color: #94a3b8; font-family: system-ui, -apple-system, sans-serif;">

            <div class="p-5">
                <!-- Logo BloomGift Admin -->
                <div class="flex items-center gap-3 px-2 py-3 mb-6 border-b border-gray-800/80">
                    <div class="w-10 h-10 rounded-2xl flex items-center justify-center text-rose-500 shadow-md"
                        style="background: rgba(244, 63, 94, 0.12); border: 1px solid rgba(244, 63, 94, 0.25);">
                        <!-- Icon Hoa Logo -->
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21V12m0 0a4.5 4.5 0 004.5-4.5c0-1.5-.7-2.5-1.5-3m-3 7.5a4.5 4.5 0 01-4.5-4.5c0-1.5.7-2.5 1.5-3m6 0a3 3 0 00-6 0m6 0c.5.8.5 1.8 0 2.5m-6-2.5c-.5.8-.5 1.8 0 2.5" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-[15px] font-bold text-white tracking-wide leading-tight">BloomGift</h1>
                        <p class="text-[11px] font-medium tracking-wider text-gray-500">Admin Panel</p>
                    </div>
                </div>

                <!-- Navigation Links -->
                <nav class="space-y-1.5 text-xs font-medium">

                    <!-- 1. Trang chủ / Bảng điều khiển -->
                    <a href="{{ route('admin.dashboard') }}"
                        class="flex items-center justify-between px-4 py-3 rounded-2xl transition-all duration-200 group {{ request()->routeIs('admin.dashboard') ? 'font-bold shadow-md' : 'text-gray-400 hover:text-gray-100 hover:bg-white/[0.05]' }}"
                        style="{{ request()->routeIs('admin.dashboard') ? 'background: linear-gradient(90deg, #fbcfe8 0%, #ffd7e2 100%); color: #1e1b24;' : '' }}">
                        <div class="flex items-center gap-3">
                            <!-- Icon Nhà -->
                            <svg class="w-4 h-4 {{ request()->routeIs('admin.dashboard') ? 'text-gray-900' : 'text-gray-400 group-hover:text-white' }}"
                                fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            <span>Trang chủ</span>
                        </div>
                        @if(!request()->routeIs('admin.dashboard'))
                            <svg class="w-3.5 h-3.5 text-gray-600 group-hover:text-gray-400" fill="none"
                                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        @endif
                    </a>

                    <!-- 2. Quản lý sản phẩm hoa -->
                    <a href="{{ route('admin.products.index') }}"
                        class="flex items-center justify-between px-4 py-3 rounded-2xl transition-all duration-200 group {{ request()->routeIs('admin.products.*') ? 'font-bold shadow-md' : 'text-gray-400 hover:text-gray-100 hover:bg-white/[0.05]' }}"
                        style="{{ request()->routeIs('admin.products.*') ? 'background: linear-gradient(90deg, #fbcfe8 0%, #ffd7e2 100%); color: #1e1b24;' : '' }}">
                        <div class="flex items-center gap-3">
                            <!-- Icon Khối hộp sản phẩm -->
                            <svg class="w-4 h-4 {{ request()->routeIs('admin.products.*') ? 'text-gray-900' : 'text-gray-400 group-hover:text-white' }}"
                                fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            <span>Quản lý sản phẩm</span>
                        </div>
                        @if(!request()->routeIs('admin.products.*'))
                            <svg class="w-3.5 h-3.5 text-gray-600 group-hover:text-gray-400" fill="none"
                                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        @endif
                    </a>

                    <!-- 3. Quản lý đơn hàng -->
                    <a href="{{ route('admin.orders.index') }}"
                        class="flex items-center justify-between px-4 py-3 rounded-2xl transition-all duration-200 group {{ request()->routeIs('admin.orders.*') ? 'font-bold shadow-md' : 'text-gray-400 hover:text-gray-100 hover:bg-white/[0.05]' }}"
                        style="{{ request()->routeIs('admin.orders.*') ? 'background: linear-gradient(90deg, #fbcfe8 0%, #ffd7e2 100%); color: #1e1b24;' : '' }}">
                        <div class="flex items-center gap-3">
                            <!-- Icon Thư mục tài liệu / Hóa đơn -->
                            <svg class="w-4 h-4 {{ request()->routeIs('admin.orders.*') ? 'text-gray-900' : 'text-gray-400 group-hover:text-white' }}"
                                fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                            </svg>
                            <span>Quản lý đơn hàng</span>
                        </div>
                        @if(!request()->routeIs('admin.orders.*'))
                            <svg class="w-3.5 h-3.5 text-gray-600 group-hover:text-gray-400" fill="none"
                                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        @endif
                    </a>

                    <!-- 4. Quản lý danh mục dịp lễ -->
                    <a href="{{ route('admin.categories.index') }}"
                        class="flex items-center justify-between px-4 py-3 rounded-2xl transition-all duration-200 group {{ request()->routeIs('admin.categories.*') ? 'font-bold shadow-md' : 'text-gray-400 hover:text-gray-100 hover:bg-white/[0.05]' }}"
                        style="{{ request()->routeIs('admin.categories.*') ? 'background: linear-gradient(90deg, #fbcfe8 0%, #ffd7e2 100%); color: #1e1b24;' : '' }}">
                        <div class="flex items-center gap-3">
                            <!-- Icon Thẻ Tag danh mục -->
                            <svg class="w-4 h-4 {{ request()->routeIs('admin.categories.*') ? 'text-gray-900' : 'text-gray-400 group-hover:text-white' }}"
                                fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                            <span>Quản lý danh mục</span>
                        </div>
                        @if(!request()->routeIs('admin.categories.*'))
                            <svg class="w-3.5 h-3.5 text-gray-600 group-hover:text-gray-400" fill="none"
                                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        @endif
                    </a>

                    <!-- 5. Khuyến mại & Voucher -->
                    <a href="{{ route('admin.vouchers.index') }}"
                        class="flex items-center justify-between px-4 py-3 rounded-2xl transition-all duration-200 group {{ request()->routeIs('admin.vouchers.*') ? 'font-bold shadow-md' : 'text-gray-400 hover:text-gray-100 hover:bg-white/[0.05]' }}"
                        style="{{ request()->routeIs('admin.vouchers.*') ? 'background: linear-gradient(90deg, #fbcfe8 0%, #ffd7e2 100%); color: #1e1b24;' : '' }}">
                        <div class="flex items-center gap-3">
                            <!-- Icon Vé Voucher / Kim cương -->
                            <svg class="w-4 h-4 {{ request()->routeIs('admin.vouchers.*') ? 'text-gray-900' : 'text-gray-400 group-hover:text-white' }}"
                                fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                            </svg>
                            <span>Khuyến mại & Voucher</span>
                        </div>
                        @if(!request()->routeIs('admin.vouchers.*'))
                            <svg class="w-3.5 h-3.5 text-gray-600 group-hover:text-gray-400" fill="none"
                                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        @endif
                    </a>

                    <!-- 6. Quản lý khung giờ giao nhận hoa -->
                    <a href="{{ route('admin.delivery-slots.index') }}"
                        class="flex items-center justify-between px-4 py-3 rounded-2xl transition-all duration-200 group {{ request()->routeIs('admin.delivery-slots.*') ? 'font-bold shadow-md' : 'text-gray-400 hover:text-gray-100 hover:bg-white/[0.05]' }}"
                        style="{{ request()->routeIs('admin.delivery-slots.*') ? 'background: linear-gradient(90deg, #fbcfe8 0%, #ffd7e2 100%); color: #1e1b24;' : '' }}">
                        <div class="flex items-center gap-3">
                            <!-- Icon Đồng hồ khung giờ -->
                            <svg class="w-4 h-4 {{ request()->routeIs('admin.delivery-slots.*') ? 'text-gray-900' : 'text-gray-400 group-hover:text-white' }}"
                                fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Quản lý khung giờ</span>
                        </div>
                        @if(!request()->routeIs('admin.delivery-slots.*'))
                            <svg class="w-3.5 h-3.5 text-gray-600 group-hover:text-gray-400" fill="none"
                                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        @endif
                    </a>

                    <!-- 7. Quản lý người dùng -->
                    <a href="{{ route('admin.users.index') }}"
                        class="flex items-center justify-between px-4 py-3 rounded-2xl transition-all duration-200 group {{ request()->routeIs('admin.users.*') ? 'font-bold shadow-md' : 'text-gray-400 hover:text-gray-100 hover:bg-white/[0.05]' }}"
                        style="{{ request()->routeIs('admin.users.*') ? 'background: linear-gradient(90deg, #fbcfe8 0%, #ffd7e2 100%); color: #1e1b24;' : '' }}">
                        <div class="flex items-center gap-3">
                            <!-- Icon Tài khoản User -->
                            <svg class="w-4 h-4 {{ request()->routeIs('admin.users.*') ? 'text-gray-900' : 'text-gray-400 group-hover:text-white' }}"
                                fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <span>Quản lý người dùng</span>
                        </div>
                        @if(!request()->routeIs('admin.users.*'))
                            <svg class="w-3.5 h-3.5 text-gray-600 group-hover:text-gray-400" fill="none"
                                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        @endif
                    </a>

                </nav>
            </div>

            <!-- Họa tiết hoa chìm mờ góc dưới bên trái -->
            <div class="px-6 py-2 opacity-15 pointer-events-none">
                <svg class="w-20 h-20 text-rose-300" fill="none" stroke="currentColor" stroke-width="1.2"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21V12m0 0a4.5 4.5 0 004.5-4.5c0-1.5-.7-2.5-1.5-3m-3 7.5a4.5 4.5 0 01-4.5-4.5c0-1.5.7-2.5 1.5-3m6 0a3 3 0 00-6 0m6 0c.5.8.5 1.8 0 2.5m-6-2.5c-.5.8-.5 1.8 0 2.5" />
                </svg>
            </div>

            <!-- Nút Đăng xuất dạng bo viền cong viền mờ -->
            <div class="p-5 border-t border-gray-800/80">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="w-full flex items-center justify-center gap-2.5 px-4 py-2.5 rounded-full border border-gray-700/80 text-xs font-semibold text-gray-400 hover:text-white hover:border-rose-400 hover:bg-rose-500/10 transition-all duration-200">
                        <!-- Icon Đăng xuất mũi tên ra ngoài -->
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span>Đăng xuất</span>
                    </button>
                </form>
            </div>

        </aside>

        <!-- Main Content Area -->
        <div class="flex min-w-0 flex-1 flex-col">
            <!-- Header chuẩn phong cách BloomGift -->
            <header
                class="sticky top-0 z-10 flex h-16 items-center justify-between border-b border-rose-100 bg-white/80 px-4 shadow-sm backdrop-blur sm:px-8">
                <div class="flex items-center gap-4">
                    <button type="button" @click="menuOpen = true" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-rose-500 transition hover:bg-rose-50 hover:text-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-200 lg:hidden" aria-label="Mở menu quản trị">
                        <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3.5 5.5h13M3.5 10h13M3.5 14.5h13" stroke-linecap="round" /></svg>
                    </button>
                    <span
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-rose-50 text-rose-500 text-xs font-medium border border-rose-100">
                        <svg aria-hidden="true" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M10 16.5V10m0 0C6.2 10 5.5 7.3 6.5 5.2 8.8 5.4 10 7.1 10 10Zm0 0c3.8 0 4.5-2.7 3.5-4.8C11.2 5.4 10 7.1 10 10Zm0 0c-1.2 0-2.8 1.4-2.8 3.3 1.9.3 3.1-.7 2.8-3.3Zm0 0c1.2 0 2.8 1.4 2.8 3.3-1.9.3-3.1-.7-2.8-3.3Z" stroke-linecap="round" stroke-linejoin="round" /></svg>
                        <span class="hidden sm:inline">Chào mừng bạn đến với BloomGift Admin</span>
                        <span class="sm:hidden">BloomGift Admin</span>
                    </span>
                </div>
                <div class="flex items-center gap-4 text-xs">
                    <button type="button" class="relative inline-flex h-9 w-9 items-center justify-center rounded-lg text-gray-400 transition hover:bg-rose-50 hover:text-rose-500" aria-label="Thông báo">
                        <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M14.5 8.3a4.5 4.5 0 0 0-9 0c0 5-2 5.4-2 6.2h13c0-.8-2-1.2-2-6.2ZM8 16.5h4" stroke-linecap="round" stroke-linejoin="round" /></svg>
                        <span class="w-1.5 h-1.5 bg-rose-500 rounded-full absolute -top-0.5 -right-0.5"></span>
                    </button>
                    <div class="flex items-center gap-2">
                        <span
                            class="w-7 h-7 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center font-bold text-xs">
                            {{ strtoupper(substr(Auth::user()->name ?? 'A', 0, 1)) }}
                        </span>
                        <span class="text-gray-600">Xin chào, <b
                                class="text-gray-800">{{ Auth::user()->name }}</b></span>
                    </div>
                </div>
            </header>

            <!-- Nội dung chính -->
            <main class="flex-1 space-y-6 p-4 sm:p-6 lg:p-8">
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
