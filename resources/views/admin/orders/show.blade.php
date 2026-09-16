@extends('layouts.admin')

@section('title', 'Chi tiết đơn hàng #' . $order->id)

@section('content')

<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-xl shadow-md border border-gray-200">

        <div>

            <div class="flex items-center gap-3">

                <h2 class="text-2xl font-bold text-gray-800">
                    Đơn hàng #ORD-{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}
                </h2>

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
                        'confirmed' => 'bg-blue-100 text-blue-800',
                        'processing' => 'bg-indigo-100 text-indigo-800',
                        'shipping' => 'bg-orange-100 text-orange-800',
                        'completed' => 'bg-green-100 text-green-800',
                        'cancelled' => 'bg-red-100 text-red-800',
                    ];
                @endphp

                <span
                    class="px-3 py-1 text-xs font-bold rounded-full {{ $statusClasses[$order->order_status] ?? 'bg-gray-100 text-gray-700' }}"
                >
                    {{ $statusLabels[$order->order_status] ?? $order->order_status }}
                </span>

            </div>

            <p class="text-sm text-gray-500 mt-1">
                Ngày đặt:
                {{ optional($order->created_at)->format('d/m/Y H:i') }}
            </p>

        </div>

        <a
            href="{{ route('admin.orders.index') }}"
            class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50 font-medium"
        >
            &larr; Quay lại danh sách
        </a>

    </div>


    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- LEFT -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Sản phẩm -->
            <div class="bg-white rounded-xl shadow-md p-6 border border-gray-200">

                <h3 class="font-bold text-gray-800 text-lg mb-4 pb-2 border-b">
                    🌸 Sản phẩm trong đơn
                </h3>

                <div class="space-y-4">

                    @forelse($order->items as $item)

                        <div class="flex items-center justify-between gap-4 border-b border-gray-100 pb-4">

                            <div>

                                <div class="font-semibold text-gray-900">
                                    {{ $item->product_name }}
                                </div>

                                <div class="text-sm text-gray-500 mt-1">
                                    {{ number_format((float) $item->price, 0, ',', '.') }} đ
                                    &times;
                                    {{ $item->quantity }}
                                </div>

                            </div>

                            <div class="font-bold text-gray-900 whitespace-nowrap">
                                {{ number_format((float) $item->subtotal, 0, ',', '.') }} đ
                            </div>

                        </div>

                    @empty

                        <div class="text-center text-gray-400 py-6">
                            Đơn hàng chưa có sản phẩm.
                        </div>

                    @endforelse

                </div>


                <!-- Tổng -->
                <div class="pt-4 mt-4 border-t space-y-2 text-sm">

                    <div class="flex justify-between">
                        <span>Tạm tính:</span>

                        <span class="font-semibold">
                            {{ number_format((float) $order->subtotal, 0, ',', '.') }} đ
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span>Giảm giá:</span>

                        <span class="text-red-500">
                            -{{ number_format((float) $order->discount, 0, ',', '.') }} đ
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span>Phí giao hàng:</span>

                        <span class="font-semibold">
                            {{ number_format((float) $order->shipping_fee, 0, ',', '.') }} đ
                        </span>
                    </div>

                    <div class="flex justify-between text-base font-bold text-rose-600 pt-2 border-t">
                        <span>Tổng thanh toán:</span>

                        <span>
                            {{ number_format((float) $order->total, 0, ',', '.') }} đ
                        </span>
                    </div>

                </div>

            </div>


            <!-- Ghi chú -->
            <div class="bg-rose-50 rounded-xl p-6 border border-rose-200">

                <h3 class="font-bold text-rose-900 text-base mb-2 flex items-center gap-2">
                    📝 Ghi chú đơn hàng
                </h3>

                <div class="p-4 bg-white rounded-lg border border-rose-100 text-gray-800 text-sm">

                    @if($order->note)

                        {{ $order->note }}

                    @else

                        <span class="text-gray-400">
                            Khách hàng không có ghi chú.
                        </span>

                    @endif

                </div>

            </div>

        </div>


        <!-- RIGHT -->
        <div class="space-y-6">

            <!-- Cập nhật trạng thái -->
            <div class="bg-white rounded-xl shadow-md p-6 border border-gray-200">

                <h3 class="font-bold text-gray-800 text-base mb-4 pb-2 border-b">
                    🔄 Cập nhật trạng thái đơn
                </h3>

                <form
                    action="{{ route('admin.orders.update-status', $order) }}"
                    method="POST"
                    class="space-y-4"
                >

                    @csrf
                    @method('PATCH')

                    <div>

                        <label class="block text-xs font-semibold text-gray-600 mb-1">
                            Trạng thái đơn
                        </label>

                        <select
                            name="status"
                            class="w-full rounded-lg border border-gray-300 p-2.5 text-sm font-semibold"
                        >

                            <option
                                value="pending"
                                {{ $order->order_status === 'pending' ? 'selected' : '' }}
                            >
                                Chờ xác nhận
                            </option>

                            <option
                                value="confirmed"
                                {{ $order->order_status === 'confirmed' ? 'selected' : '' }}
                            >
                                Đã xác nhận
                            </option>

                            <option
                                value="processing"
                                {{ $order->order_status === 'processing' ? 'selected' : '' }}
                            >
                                Đang chuẩn bị
                            </option>

                            <option
                                value="shipping"
                                {{ $order->order_status === 'shipping' ? 'selected' : '' }}
                            >
                                Đang giao hàng
                            </option>

                            <option
                                value="completed"
                                {{ $order->order_status === 'completed' ? 'selected' : '' }}
                            >
                                Hoàn tất
                            </option>

                            <option
                                value="cancelled"
                                {{ $order->order_status === 'cancelled' ? 'selected' : '' }}
                            >
                                Đã hủy
                            </option>

                        </select>

                    </div>

                    <button
                        type="submit"
                        class="w-full px-4 py-2.5 bg-gray-800 hover:bg-black text-white rounded-lg text-sm font-semibold"
                    >
                        Cập nhật trạng thái
                    </button>

                </form>

            </div>


            <!-- Thông tin người nhận -->
            <div class="bg-white rounded-xl shadow-md p-6 border border-gray-200">

                <h3 class="font-bold text-gray-800 text-base mb-4 pb-2 border-b">
                    📦 Thông tin nhận hàng
                </h3>

                <div class="space-y-4">

                    <div>
                        <span class="text-xs text-gray-500 block">
                            Người nhận
                        </span>

                        <span class="font-semibold text-gray-900">
                            {{ $order->recipient_name }}
                        </span>
                    </div>

                    <div>
                        <span class="text-xs text-gray-500 block">
                            Số điện thoại
                        </span>

                        <span class="text-gray-800">
                            {{ $order->recipient_phone }}
                        </span>
                    </div>

                    <div>
                        <span class="text-xs text-gray-500 block">
                            Địa chỉ giao hàng
                        </span>

                        <span class="text-gray-800">
                            {{ $order->recipient_address }}
                        </span>
                    </div>

                    <div class="pt-2 border-t">

                        <span class="text-xs text-gray-500 block">
                            Ngày hẹn giao
                        </span>

                        <span class="font-bold text-rose-600">
                            {{ optional($order->delivery_date)->format('d/m/Y') }}
                        </span>

                    </div>

                    <div>

                        <span class="text-xs text-gray-500 block">
                            Khung giờ giao
                        </span>

                        <span class="font-semibold text-gray-800">
                            {{ $order->deliverySlot?->name ?? 'Chưa chọn' }}
                        </span>

                    </div>

                </div>

            </div>


            <!-- Thanh toán -->
            <div class="bg-white rounded-xl shadow-md p-6 border border-gray-200">

                <h3 class="font-bold text-gray-800 text-base mb-4 pb-2 border-b">
                    💳 Thanh toán
                </h3>

                @if($order->payment)

                    <div class="space-y-3 text-sm">

                        <div class="flex justify-between">

                            <span class="text-gray-500">
                                Phương thức
                            </span>

                            <strong>
                                {{ strtoupper($order->payment->payment_method) }}
                            </strong>

                        </div>

                        <div class="flex justify-between">

                            <span class="text-gray-500">
                                Trạng thái
                            </span>

                            <strong>
                                {{ strtoupper($order->payment->status) }}
                            </strong>

                        </div>

                        <div class="flex justify-between">

                            <span class="text-gray-500">
                                Số tiền
                            </span>

                            <strong class="text-rose-600">
                                {{ number_format((float) $order->payment->amount, 0, ',', '.') }} đ
                            </strong>

                        </div>

                        @if($order->payment->transaction_id)

                            <div class="pt-2 border-t">

                                <span class="text-xs text-gray-500 block">
                                    Transaction ID
                                </span>

                                <span class="text-xs font-mono break-all">
                                    {{ $order->payment->transaction_id }}
                                </span>

                            </div>

                        @endif

                        @if($order->payment->paid_at)

                            <div>

                                <span class="text-xs text-gray-500 block">
                                    Thanh toán lúc
                                </span>

                                <span>
                                    {{ $order->payment->paid_at->format('d/m/Y H:i') }}
                                </span>

                            </div>

                        @endif

                    </div>

                @else

                    <div class="text-gray-400 text-sm">
                        Chưa có thông tin thanh toán.
                    </div>

                @endif

            </div>

        </div>

    </div>

@endsection
