@extends('layouts.admin')

@section('content')
    <div class="min-h-screen p-6 md:p-8"
        style="background-color: #fff4f7; font-family: system-ui, -apple-system, sans-serif; color: #374151;">

        <!-- ========================================================
                         1. THANH HEADER TRÊN: TÌM KIẾM & THÔNG TIN ADMIN
                         ======================================================== -->


        <!-- ========================================================
                         2. BANNER CHÀO MỪNG HOA TƯƠI
                         ======================================================== -->
        <div class="relative overflow-hidden rounded-3xl p-6 md:p-8 mb-6 shadow-sm border border-rose-100"
            style="background: linear-gradient(100deg, #ffffff 0%, #fff1f5 60%, #ffe4ec 100%);">

            <div class="relative z-10 max-w-xl">
                <div
                    class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-200 mb-3">
                    <span>✨ Chào mừng bạn đến với</span>
                </div>
                <h2 class="text-2xl md:text-3xl font-black text-gray-900 mb-2 tracking-tight">
                    BloomGift Admin <span class="text-rose-500">🌸</span>
                </h2>
                <p class="text-xs md:text-sm text-gray-500 leading-relaxed">
                    Cùng nhau mang những đóa hoa tươi thắm đến mọi khoảnh khắc đẹp!
                </p>
            </div>

            <!-- Hình bó hoa nghệ thuật góc phải banner -->
            <div
                class="absolute right-4 -bottom-4 md:right-10 md:-bottom-6 flex items-center pointer-events-none select-none">
                <span class="text-rose-400 font-serif italic text-xs md:text-sm mr-2 hidden sm:inline"
                    style="font-family: 'Brush Script MT', cursive, sans-serif;">
                    Flowers make life better ♡
                </span>
                <div class="w-28 h-28 md:w-40 md:h-40 rounded-full flex items-center justify-center shadow-inner"
                    style="background: radial-gradient(circle, rgba(254,226,226,0.6) 0%, rgba(255,255,255,0) 70%);">
                    <span class="text-6xl md:text-7xl drop-shadow-md">💐</span>
                </div>
            </div>
        </div>

        <!-- ========================================================
                         3. 4 THẺ THỐNG KÊ (METRICS CARDS KÈM SPARKLINE)
                         ======================================================== -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

            <!-- Thẻ 1: Doanh thu hoàn tất -->
            <div class="bg-white rounded-2xl p-5 border border-rose-100/80 shadow-sm relative overflow-hidden">
                <div class="flex items-center justify-between mb-3">
                    <div
                        class="w-9 h-9 rounded-xl flex items-center justify-center text-rose-600 bg-rose-50 border border-rose-100">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                    <span class="text-[11px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full"></span>
                </div>
                <p class="text-[11px] font-medium text-gray-400">Doanh thu hoàn tất</p>
                <h3 class="text-xl font-black text-gray-900 tracking-tight my-0.5">
                    {{ number_format($totalRevenue, 0, ',', '.') }} đ
                </h3>
                <p class="text-[10px] text-gray-400">Từ số đơn giao thành công</p>

                <!-- Sóng sparkline hồng -->
                <svg class="w-full h-6 mt-2 text-rose-400" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 100 25">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M0 20 Q 25 5, 50 15 T 100 8" />
                </svg>
            </div>

            <!-- Thẻ 2: Đơn cần xử lý & cắm hoa -->
            <div class="bg-white rounded-2xl p-5 border border-purple-100/80 shadow-sm relative overflow-hidden">
                <div class="flex items-center justify-between mb-3">
                    <div
                        class="w-9 h-9 rounded-xl flex items-center justify-center text-purple-600 bg-purple-50 border border-purple-100">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <span
                        class="text-[11px] font-bold text-purple-600 bg-purple-50 px-2 py-0.5 rounded-full">{{ $totalOrders }}
                        tổng đơn</span>
                </div>
                <p class="text-[11px] font-medium text-gray-400">Đơn cần xử lý & cắm hoa</p>
                <h3 class="text-xl font-black text-gray-900 tracking-tight my-0.5">
                    {{ $pendingOrders }}
                </h3>
                <p class="text-[10px] text-gray-400">Chờ duyệt / Đang cắm hoa</p>

                <!-- Sóng sparkline tím -->
                <svg class="w-full h-6 mt-2 text-purple-400" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 100 25">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M0 18 Q 20 22, 40 10 T 70 14 T 100 5" />
                </svg>
            </div>

            <!-- Thẻ 3: Tổng sản phẩm hoa -->
            <div class="bg-white rounded-2xl p-5 border border-emerald-100/80 shadow-sm relative overflow-hidden">
                <div class="flex items-center justify-between mb-3">
                    <div
                        class="w-9 h-9 rounded-xl flex items-center justify-center text-emerald-600 bg-emerald-50 border border-emerald-100">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <span class="text-[11px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">Kho
                        hoa</span>
                </div>
                <p class="text-[11px] font-medium text-gray-400">Tổng sản phẩm hoa</p>
                <h3 class="text-xl font-black text-gray-900 tracking-tight my-0.5">
                    {{ $totalProducts }}
                </h3>
                <p class="text-[10px] text-gray-400">Đang hoạt động trong kho</p>

                <!-- Sóng sparkline xanh ngọc -->
                <svg class="w-full h-6 mt-2 text-emerald-400" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 100 25">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M0 15 Q 30 5, 60 20 T 100 10" />
                </svg>
            </div>

            <!-- Thẻ 4: Khách hàng đăng ký -->
            <div class="bg-white rounded-2xl p-5 border border-amber-100/80 shadow-sm relative overflow-hidden">
                <div class="flex items-center justify-between mb-3">
                    <div
                        class="w-9 h-9 rounded-xl flex items-center justify-center text-amber-600 bg-amber-50 border border-amber-100">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <span class="text-[11px] font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full"></span>
                </div>
                <p class="text-[11px] font-medium text-gray-400">Khách hàng đăng ký</p>
                <h3 class="text-xl font-black text-gray-900 tracking-tight my-0.5">
                    {{ $totalCustomers }}
                </h3>
                <p class="text-[10px] text-gray-400">Tài khoản thành viên</p>

                <!-- Sóng sparkline cam -->
                <svg class="w-full h-6 mt-2 text-amber-400" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 100 25">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M0 22 Q 25 18, 50 12 T 100 6" />
                </svg>
            </div>

        </div>

        <!-- ========================================================
                         4. KHỐI GIỮA: BANNER VOUCHER & CỤM THAO TÁC NHANH
                         ======================================================== -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 mb-6">

            <!-- Banner Khuyến mại & Voucher (Bên trái) -->
            <div class="lg:col-span-5 rounded-2xl p-6 text-white relative overflow-hidden shadow-sm flex flex-col justify-between"
                style="background: linear-gradient(135deg, #fb7185 0%, #f43f5e 50%, #e11d48 100%);">

                <div class="relative z-10">
                    <div class="flex items-center gap-2 mb-2 text-rose-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                        </svg>
                        <span class="text-xs font-semibold uppercase tracking-wider">Khuyến mại & Voucher</span>
                    </div>
                    <h4 class="text-3xl font-black my-1">
                        {{ \App\Models\Voucher::count() ?? 5 }}
                    </h4>
                    <p class="text-xs text-rose-100">Chương trình Flash Sale đang hoạt động</p>
                </div>

                <!-- Nút Quản lý ngay -->
                <div class="relative z-10 mt-5 flex items-center justify-between">
                    <a href="{{ route('admin.vouchers.index') }}"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-xs font-bold text-rose-600 bg-white hover:bg-rose-50 shadow-md transition-all">
                        <span>Quản lý ngay</span>
                        <span>&rarr;</span>
                    </a>
                    <span class="text-5xl opacity-90 drop-shadow">🎁</span>
                </div>

                <!-- Họa tiết mờ nền sau -->
                <div class="absolute -right-8 -bottom-8 w-36 h-36 bg-white/10 rounded-full blur-2xl pointer-events-none">
                </div>
            </div>

            <!-- Cụm Thao tác nhanh (Bên phải - Grid 2x2) -->
            <div
                class="lg:col-span-7 bg-white rounded-2xl p-6 border border-rose-100 shadow-sm flex flex-col justify-between">
                <div class="flex items-center gap-2 mb-4">
                    <span class="text-rose-500 font-bold">⚡</span>
                    <h4 class="text-xs font-bold text-gray-800 uppercase tracking-wider">Thao tác nhanh</h4>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <!-- Nút 1: Thêm sản phẩm -->
                    <a href="{{ route('admin.products.create') }}"
                        class="flex items-center justify-between p-3.5 rounded-xl border border-rose-100 hover:border-rose-300 hover:bg-rose-50/50 transition-all group">
                        <div class="flex items-center gap-3">
                            <span
                                class="w-7 h-7 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center text-xs font-bold">＋</span>
                            <span class="text-xs font-semibold text-gray-700 group-hover:text-rose-600">Thêm sản phẩm</span>
                        </div>
                        <span class="text-gray-400 group-hover:text-rose-500 text-xs">›</span>
                    </a>

                    <!-- Nút 2: Xem đơn hàng -->
                    <a href="{{ route('admin.orders.index') }}"
                        class="flex items-center justify-between p-3.5 rounded-xl border border-purple-100 hover:border-purple-300 hover:bg-purple-50/50 transition-all group">
                        <div class="flex items-center gap-3">
                            <span
                                class="w-7 h-7 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center text-xs font-bold">📋</span>
                            <span class="text-xs font-semibold text-gray-700 group-hover:text-purple-600">Quản lý đơn
                                hàng</span>
                        </div>
                        <span class="text-gray-400 group-hover:text-purple-500 text-xs">›</span>
                    </a>

                    <!-- Nút 3: Tạo danh mục dịp lễ -->
                    <a href="{{ route('admin.categories.index') }}"
                        class="flex items-center justify-between p-3.5 rounded-xl border border-emerald-100 hover:border-emerald-300 hover:bg-emerald-50/50 transition-all group">
                        <div class="flex items-center gap-3">
                            <span
                                class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center text-xs font-bold">🏷️</span>
                            <span class="text-xs font-semibold text-gray-700 group-hover:text-emerald-600">Danh mục dịp
                                lễ</span>
                        </div>
                        <span class="text-gray-400 group-hover:text-emerald-500 text-xs">›</span>
                    </a>

                    <!-- Nút 4: Tạo voucher -->
                    <a href="{{ route('admin.vouchers.index') }}"
                        class="flex items-center justify-between p-3.5 rounded-xl border border-amber-100 hover:border-amber-300 hover:bg-amber-50/50 transition-all group">
                        <div class="flex items-center gap-3">
                            <span
                                class="w-7 h-7 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center text-xs font-bold">🎟️</span>
                            <span class="text-xs font-semibold text-gray-700 group-hover:text-amber-600">Tạo voucher
                                mới</span>
                        </div>
                        <span class="text-gray-400 group-hover:text-amber-500 text-xs">›</span>
                    </a>
                </div>
            </div>

        </div>

        <!-- ========================================================
                         5. BẢNG ĐƠN ĐẶT HOA MỚI NHẤT
                         ======================================================== -->
        <div class="bg-white rounded-2xl border border-rose-100 shadow-sm overflow-hidden">

            <!-- Tiêu đề bảng -->
            <div class="p-5 border-b border-rose-50 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-rose-500 text-base">🌸</span>
                    <h3 class="text-xs font-bold text-gray-800 uppercase tracking-wider">Đơn đặt hoa mới nhất</h3>
                </div>
                <a href="{{ route('admin.orders.index') }}"
                    class="text-xs font-bold text-rose-500 hover:text-rose-700 transition-colors">
                    Xem tất cả &rarr;
                </a>
            </div>

            <!-- Bảng danh sách đơn -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead
                        class="bg-rose-50/50 text-[11px] font-bold text-gray-500 uppercase tracking-wider border-b border-rose-50">
                        <tr>
                            <th class="py-3.5 px-5">MÃ ĐƠN</th>
                            <th class="py-3.5 px-5">NGƯỜI NHẬN</th>
                            <th class="py-3.5 px-5">TỔNG TIỀN</th>
                            <th class="py-3.5 px-5 text-center">TRẠNG THÁI</th>
                            <th class="py-3.5 px-5 text-right">HÀNH ĐỘNG</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-rose-50 font-medium">
                        @forelse($recentOrders as $order)
                            @php
                                $rawStatus = strtoupper($order->status ?? $order->order_status ?? 'PENDING');
                                $amount = $order->total_amount ?? ($order->total ?? 0);
                            @endphp
                            <tr class="hover:bg-rose-50/30 transition-colors">
                                <!-- Mã đơn -->
                                <td class="py-4 px-5 font-bold text-gray-900">
                                    #{{ $order->order_code ?? ('ORD-' . str_pad($order->id, 5, '0', STR_PAD_LEFT)) }}
                                </td>

                                <!-- Người nhận -->
                                <td class="py-4 px-5 text-gray-700">
                                    {{ $order->recipient_name ?? ($order->user->name ?? 'Khách Hàng') }}
                                </td>

                                <!-- Tổng tiền -->
                                <td class="py-4 px-5 font-bold text-rose-600">
                                    {{ number_format($amount, 0, ',', '.') }} đ
                                </td>

                                <!-- Trạng thái viên thuốc bo tròn -->
                                <td class="py-4 px-5 text-center">
                                    @if(in_array($rawStatus, ['COMPLETED', 'DELIVERED']))
                                        <span
                                            class="inline-block px-3 py-1 rounded-full text-[10px] font-extrabold tracking-wider bg-emerald-100 text-emerald-700">
                                            COMPLETED
                                        </span>
                                    @elseif($rawStatus === 'CONFIRMED')
                                        <span
                                            class="inline-block px-3 py-1 rounded-full text-[10px] font-extrabold tracking-wider bg-rose-100 text-rose-600">
                                            CONFIRMED
                                        </span>
                                    @elseif($rawStatus === 'PREPARING')
                                        <span
                                            class="inline-block px-3 py-1 rounded-full text-[10px] font-extrabold tracking-wider bg-purple-100 text-purple-700">
                                            PREPARING
                                        </span>
                                    @elseif($rawStatus === 'SHIPPING')
                                        <span
                                            class="inline-block px-3 py-1 rounded-full text-[10px] font-extrabold tracking-wider bg-sky-100 text-sky-700">
                                            SHIPPING
                                        </span>
                                    @elseif($rawStatus === 'CANCELLED')
                                        <span
                                            class="inline-block px-3 py-1 rounded-full text-[10px] font-extrabold tracking-wider bg-red-100 text-red-600">
                                            CANCELLED
                                        </span>
                                    @else
                                        <span
                                            class="inline-block px-3 py-1 rounded-full text-[10px] font-extrabold tracking-wider bg-amber-100 text-amber-700">
                                            PENDING
                                        </span>
                                    @endif
                                </td>

                                <!-- Hành động -->
                                <td class="py-4 px-5 text-right">
                                    <a href="{{ route('admin.orders.show', $order) }}"
                                        class="text-xs font-bold text-rose-500 hover:text-rose-700 hover:underline">
                                        Chi tiết
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-gray-400">
                                    Chưa có đơn đặt hoa nào gần đây.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>

    </div>
@endsection