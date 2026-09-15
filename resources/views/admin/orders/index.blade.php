@extends('layouts.admin')
@section('title', 'Quản lý đơn hàng hoa & quà')

@section('content')
    <div class="bg-white rounded-xl shadow-md p-6 border border-gray-200">
        <div
            class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6 pb-4 border-b border-gray-100">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Danh sách đơn đặt hàng</h2>
                <p class="text-sm text-gray-500">Theo dõi tiến độ cắm hoa và lịch giao hẹn cho khách</p>
            </div>
        </div>

        <!-- Bộ lọc trạng thái & tìm kiếm -->
        <form method="GET" action="{{ route('admin.orders.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="🔍 Tìm tên hoặc SĐT người nhận..."
                class="rounded-lg border border-gray-300 px-4 py-2 text-sm focus:ring-2 focus:ring-rose-500 focus:outline-none">

            <select name="status"
                class="rounded-lg border border-gray-300 px-4 py-2 text-sm focus:ring-2 focus:ring-rose-500 focus:outline-none bg-white">
                <option value="">-- Tất cả trạng thái --</option>
                <option value="PENDING" {{ request('status') == 'PENDING' ? 'selected' : '' }}>Chờ xử lý (PENDING)</option>
                <option value="CONFIRMED" {{ request('status') == 'CONFIRMED' ? 'selected' : '' }}>Đã xác nhận (CONFIRMED)
                </option>
                <option value="PREPARING" {{ request('status') == 'PREPARING' ? 'selected' : '' }}>Đang cắm hoa (PREPARING)
                </option>
                <option value="SHIPPING" {{ request('status') == 'SHIPPING' ? 'selected' : '' }}>Đang giao hàng (SHIPPING)
                </option>
                <option value="COMPLETED" {{ request('status') == 'COMPLETED' ? 'selected' : '' }}>Hoàn tất (COMPLETED)
                </option>
                <option value="CANCELLED" {{ request('status') == 'CANCELLED' ? 'selected' : '' }}>Đã hủy (CANCELLED)</option>
            </select>

            <div class="flex gap-2">
                <button type="submit"
                    class="px-5 py-2 bg-gray-800 hover:bg-black text-white rounded-lg text-sm font-semibold">Lọc
                    đơn</button>
                <a href="{{ route('admin.orders.index') }}"
                    class="px-4 py-2 border border-gray-300 text-gray-600 rounded-lg text-sm hover:bg-gray-50">Đặt lại</a>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600 border border-gray-200 rounded-lg">
                <thead class="bg-gray-100 text-gray-700 uppercase text-xs font-bold border-b border-gray-200">
                    <tr>
                        <th class="p-3.5">Mã đơn</th>
                        <th class="p-3.5">Người nhận & SĐT</th>
                        <th class="p-3.5">Ngày hẹn giao</th>
                        <th class="p-3.5">Khung giờ giao</th>
                        <th class="p-3.5">Tổng tiền</th>
                        <th class="p-3.5">Trạng thái</th>
                        <th class="p-3.5 text-center">Chi tiết</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($orders as $ord)
                        <tr class="hover:bg-rose-50/40 transition">
                            <td class="p-3.5 font-bold text-gray-900">#ORD-{{ str_pad($ord->id, 5, '0', STR_PAD_LEFT) }}</td>
                            <td class="p-3.5">
                                <div class="font-semibold text-gray-900">{{ $ord->recipient_name }}</div>
                                <div class="text-xs text-gray-500 font-mono">{{ $ord->recipient_phone }}</div>
                            </td>
                            <td class="p-3.5 font-medium text-gray-700">
                                {{ \Carbon\Carbon::parse($ord->delivery_date)->format('d/m/Y') }}
                            </td>
                            <td class="p-3.5">
                                <span
                                    class="px-2.5 py-1 text-xs rounded bg-purple-50 text-purple-700 font-medium border border-purple-100">
                                    {{ $ord->deliverySlot ? $ord->deliverySlot->time_range : 'Tiêu chuẩn' }}
                                </span>
                            </td>
                            <td class="p-3.5 font-bold text-rose-600">
                                {{ number_format($ord->total_amount, 0, ',', '.') }} đ
                            </td>
                            <td class="p-3.5">
                                @php
                                    $badges = [
                                        'PENDING' => 'bg-yellow-100 text-yellow-800',
                                        'CONFIRMED' => 'bg-blue-100 text-blue-800',
                                        'PREPARING' => 'bg-indigo-100 text-indigo-800',
                                        'SHIPPING' => 'bg-orange-100 text-orange-800',
                                        'COMPLETED' => 'bg-green-100 text-green-800',
                                        'CANCELLED' => 'bg-red-100 text-red-800',
                                    ];
                                @endphp
                                <span
                                    class="px-2.5 py-1 text-xs rounded-full font-bold {{ $badges[$ord->status] ?? 'bg-gray-100' }}">
                                    {{ $ord->status }}
                                </span>
                            </td>
                            <td class="p-3.5 text-center">
                                <a href="{{ route('admin.orders.show', $ord) }}"
                                    class="font-bold text-rose-600 hover:text-rose-800 hover:underline text-sm">
                                    👁️ Xem chi tiết
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-gray-400">Chưa có đơn hàng nào được tạo.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $orders->links() }}</div>
    </div>
@endsection