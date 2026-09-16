@extends('layouts.admin')
@section('title', 'Bảng điều khiển')

@section('content')
    <div class="space-y-6">
        <!-- Tiêu đề trang -->
        <div>
            <h2 class="text-lg font-bold text-rose-600 flex items-center gap-2">
                🌸 Bảng điều khiển
            </h2>
            <p class="text-xs text-gray-500 mt-0.5">Tổng quan hoạt động của cửa hàng hoa</p>
        </div>

        <!-- 4 Card thống kê bo tròn có hoa minh họa -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Card 1: Doanh thu -->
            <div
                class="bg-white rounded-2xl p-5 border border-rose-100 shadow-sm flex justify-between items-center relative overflow-hidden">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-full bg-rose-100 flex items-center justify-center text-rose-500 text-lg">
                        🌷
                    </div>
                    <div>
                        <span class="text-xs text-gray-500 font-medium block">Doanh thu hoàn tất</span>
                        <span class="text-xl font-extrabold text-gray-900 mt-0.5 block">
                            {{ number_format($totalRevenue ?? 0, 0, ',', '.') }} đ
                        </span>
                        <span class="text-[11px] text-gray-400">Từ số đơn giao thành công</span>
                    </div>
                </div>
                <div class="text-3xl opacity-70 select-none">🌸</div>
            </div>

            <!-- Card 2: Đơn cần cắm hoa -->
            <div
                class="bg-white rounded-2xl p-5 border border-rose-100 shadow-sm flex justify-between items-center relative overflow-hidden">
                <div class="flex items-center gap-3">
                    <div
                        class="w-11 h-11 rounded-full bg-amber-100 flex items-center justify-center text-amber-600 text-lg">
                        📋
                    </div>
                    <div>
                        <span class="text-xs text-gray-500 font-medium block">Đơn cần xử lý & cắm hoa</span>
                        <span class="text-xl font-extrabold text-gray-900 mt-0.5 block">
                            {{ $pendingOrders ?? 0 }}
                        </span>
                        <span class="text-[11px] text-gray-400">Chờ duyệt / Đang chuẩn bị hoa</span>
                    </div>
                </div>
                <div class="text-3xl opacity-70 select-none">💐</div>
            </div>

            <!-- Card 3: Sản phẩm hoa -->
            <div
                class="bg-white rounded-2xl p-5 border border-rose-100 shadow-sm flex justify-between items-center relative overflow-hidden">
                <div class="flex items-center gap-3">
                    <div
                        class="w-11 h-11 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 text-lg">
                        🛒
                    </div>
                    <div>
                        <span class="text-xs text-gray-500 font-medium block">Tổng sản phẩm hoa</span>
                        <span class="text-xl font-extrabold text-gray-900 mt-0.5 block">
                            {{ $totalProducts ?? 0 }}
                        </span>
                        <span class="text-[11px] text-gray-400">Đang hoạt động trong kho</span>
                    </div>
                </div>
                <div class="text-3xl opacity-70 select-none">🌺</div>
            </div>

            <!-- Card 4: Khách hàng -->
            <div
                class="bg-white rounded-2xl p-5 border border-rose-100 shadow-sm flex justify-between items-center relative overflow-hidden">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-full bg-pink-100 flex items-center justify-center text-pink-600 text-lg">
                        👤
                    </div>
                    <div>
                        <span class="text-xs text-gray-500 font-medium block">Khách hàng đăng ký</span>
                        <span class="text-xl font-extrabold text-gray-900 mt-0.5 block">
                            {{ $totalCustomers ?? 0 }}
                        </span>
                        <span class="text-[11px] text-gray-400">Tài khoản thành viên</span>
                    </div>
                </div>
                <div class="text-3xl opacity-70 select-none">💮</div>
            </div>
        </div>

        <!-- Bảng đơn đặt hoa mới nhất -->
        <div class="bg-white rounded-2xl border border-rose-100 shadow-sm overflow-hidden">
            <div class="p-4 px-6 flex justify-between items-center border-b border-rose-100/60">
                <h3 class="font-bold text-sm text-gray-800 flex items-center gap-2">
                    🌸 Đơn đặt hoa mới nhất
                </h3>
                <a href="{{ route('admin.orders.index') }}" class="text-xs font-semibold text-rose-500 hover:underline">
                    Xem tất cả &rarr;
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-gray-600">
                    <thead class="bg-[#fff0f3] text-rose-900 font-semibold uppercase text-[11px]">
                        <tr>
                            <th class="p-3 px-6">Mã đơn</th>
                            <th class="p-3 px-6">Người nhận</th>
                            <th class="p-3 px-6">Tổng tiền</th>
                            <th class="p-3 px-6">Trạng thái</th>
                            <th class="p-3 px-6 text-right">Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-rose-50">
                        @forelse($recentOrders ?? [] as $ro)
                            <tr class="hover:bg-rose-50/40 transition">
                                <td class="p-3 px-6 font-bold text-gray-800">#ORD-{{ str_pad($ro->id, 5, '0', STR_PAD_LEFT) }}
                                </td>
                                <td class="p-3 px-6 font-medium text-gray-700">{{ $ro->recipient_name }}</td>
                                <td class="p-3 px-6 font-bold text-rose-500">{{ number_format($ro->total_amount, 0, ',', '.') }}
                                    đ</td>
                                <td class="p-3 px-6">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700">
                                        {{ $ro->status }}
                                    </span>
                                </td>
                                <td class="p-3 px-6 text-right">
                                    <a href="{{ route('admin.orders.show', $ro) }}"
                                        class="text-rose-500 font-bold hover:underline">Chi tiết</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-8 text-center text-gray-400">
                                    <div class="inline-block text-2xl mb-1">💐</div>
                                    <p class="text-xs">Chưa có đơn hàng nào</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection