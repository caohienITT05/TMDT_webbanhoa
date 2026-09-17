@extends('layouts.admin')

@section('title', 'Bảng điều khiển kinh doanh BloomGift')

@section('content')
    <div class="space-y-6">

        <!-- 4 Thẻ chỉ số tổng quan -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">

            <div class="bg-white p-6 rounded-xl shadow-md border border-gray-200">
                <div class="text-xs uppercase font-bold text-gray-400">
                    Doanh thu hoàn tất
                </div>

                <div class="mt-2 text-2xl font-extrabold text-emerald-600">
                    {{ number_format($totalRevenue, 0, ',', '.') }} đ
                </div>

                <div class="mt-1 text-xs text-gray-500">
                    Từ các đơn giao thành công
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-md border border-gray-200">
                <div class="text-xs uppercase font-bold text-gray-400">
                    Đơn cần xử lý & cắm hoa
                </div>

                <div class="mt-2 text-2xl font-extrabold text-amber-500">
                    {{ $pendingOrders }}
                </div>

                <div class="mt-1 text-xs text-gray-500">
                    Chờ duyệt / Đang chuẩn bị hoa
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-md border border-gray-200">
                <div class="text-xs uppercase font-bold text-gray-400">
                    Tổng sản phẩm hoa
                </div>

                <div class="mt-2 text-2xl font-extrabold text-gray-900">
                    {{ $totalProducts }}
                </div>

                <div class="mt-1 text-xs text-gray-500">
                    Đang hoạt động trong kho
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-md border border-gray-200">
                <div class="text-xs uppercase font-bold text-gray-400">
                    Khách hàng đăng ký
                </div>

                <div class="mt-2 text-2xl font-extrabold text-purple-600">
                    {{ $totalCustomers }}
                </div>

                <div class="mt-1 text-xs text-gray-500">
                    Tài khoản thành viên
                </div>
            </div>

        </div>

        <!-- Bảng 5 đơn hàng mới nhất -->
        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-200">

            <div class="flex justify-between items-center mb-4 pb-2 border-b">
                <h3 class="font-bold text-gray-800 text-base">
                    🕒 Đơn đặt hoa mới nhất
                </h3>

                <a
                    href="{{ route('admin.orders.index') }}"
                    class="text-xs font-bold text-rose-600 hover:underline"
                >
                    Xem tất cả &rarr;
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600">

                    <thead class="text-xs uppercase bg-gray-50 text-gray-500">
                        <tr>
                            <th class="p-3">Mã đơn</th>
                            <th class="p-3">Người nhận</th>
                            <th class="p-3">Tổng tiền</th>
                            <th class="p-3">Trạng thái</th>
                            <th class="p-3 text-right">Hành động</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">

                        @forelse($recentOrders as $ro)

                            @php
                                $statusLabels = [
                                    'pending' => 'Chờ xác nhận',
                                    'confirmed' => 'Đã xác nhận',
                                    'processing' => 'Đang chuẩn bị',
                                    'shipping' => 'Đang giao',
                                    'completed' => 'Hoàn tất',
                                    'cancelled' => 'Đã hủy',
                                ];

                                $statusClasses = [
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'confirmed' => 'bg-blue-100 text-blue-700',
                                    'processing' => 'bg-indigo-100 text-indigo-700',
                                    'shipping' => 'bg-orange-100 text-orange-700',
                                    'completed' => 'bg-green-100 text-green-700',
                                    'cancelled' => 'bg-red-100 text-red-700',
                                ];

                                $status = $ro->order_status;
                            @endphp

                            <tr>
                                <td class="p-3 font-bold text-gray-900">
                                    {{ $ro->order_code }}
                                </td>

                                <td class="p-3">
                                    {{ $ro->recipient_name }}
                                    ({{ $ro->recipient_phone }})
                                </td>

                                <td class="p-3 font-semibold text-rose-600">
                                    {{ number_format((float) $ro->total, 0, ',', '.') }} đ
                                </td>

                                <td class="p-3">
                                    <span
                                        class="px-2.5 py-0.5 text-xs font-bold rounded-full {{ $statusClasses[$status] ?? 'bg-gray-100 text-gray-700' }}"
                                    >
                                        {{ $statusLabels[$status] ?? $status }}
                                    </span>
                                </td>

                                <td class="p-3 text-right">
                                    <a
                                        href="{{ route('admin.orders.show', $ro) }}"
                                        class="font-bold text-blue-600 hover:underline text-xs"
                                    >
                                        Xem
                                    </a>
                                </td>
                            </tr>

                        @empty

                            <tr>
                                <td
                                    colspan="5"
                                    class="p-4 text-center text-gray-400"
                                >
                                    Chưa có đơn hàng nào.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>
                </table>
            </div>

        </div>

    </div>
@endsection