@extends('layouts.admin')
@section('title', 'Chi tiết đơn hàng #' . $order->id)

@section('content')
    <div class="space-y-6">
        <!-- Nút quay lại & Tiêu đề -->
        <div
            class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-xl shadow-md border border-gray-200">
            <div>
                <div class="flex items-center gap-3">
                    <h2 class="text-2xl font-bold text-gray-800">Đơn hàng
                        #ORD-{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}</h2>
                    <span class="px-3 py-1 text-xs font-bold rounded-full bg-rose-100 text-rose-700">
                        {{ $order->status }}
                    </span>
                </div>
                <p class="text-sm text-gray-500 mt-1">Ngày đặt: {{ $order->created_at->format('d/m/Y H:i') }}</p>
            </div>
            <a href="{{ route('admin.orders.index') }}"
                class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50 font-medium">
                &larr; Quay lại danh sách
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Cột trái: Danh sách hoa & Thông điệp thiệp mừng (2 phần) -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Danh sách hoa đã đặt -->
                <div class="bg-white rounded-xl shadow-md p-6 border border-gray-200">
                    <h3 class="font-bold text-gray-800 text-lg mb-4 pb-2 border-b">💐 Danh sách hoa & quà đặt mua</h3>
                    <div class="divide-y">
                        @foreach($order->items as $item)
                            <div class="py-3 flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <img src="{{ $item->product->image_url ?? '' }}"
                                        class="w-16 h-16 object-cover rounded-lg border">
                                    <div>
                                        <div class="font-bold text-gray-900">{{ $item->product->name ?? 'Sản phẩm đã xóa' }}
                                        </div>
                                        <div class="text-xs text-gray-500">Đơn giá:
                                            {{ number_format($item->price, 0, ',', '.') }} đ &times; {{ $item->quantity }}</div>
                                    </div>
                                </div>
                                <div class="font-bold text-gray-900">{{ number_format($item->total, 0, ',', '.') }} đ</div>
                            </div>
                        @endforeach
                    </div>

                    <div class="pt-4 mt-4 border-t space-y-2 text-sm text-right">
                        <div>Tạm tính: <span class="font-semibold">{{ number_format($order->subtotal, 0, ',', '.') }}
                                đ</span></div>
                        <div>Giảm giá (Voucher): <span
                                class="text-red-500">-{{ number_format($order->discount_amount, 0, ',', '.') }} đ</span>
                        </div>
                        <div class="text-base font-bold text-rose-600">Tổng thanh toán:
                            {{ number_format($order->total_amount, 0, ',', '.') }} đ</div>
                    </div>
                </div>

                <!-- Lời chúc trên thiệp mừng (Đặc thù hoa tươi BloomGift) -->
                <div class="bg-rose-50 rounded-xl p-6 border border-rose-200">
                    <h3 class="font-bold text-rose-900 text-base mb-2 flex items-center gap-2">
                        💌 Thông điệp in trên thiệp mừng trao gửi
                    </h3>
                    <div class="p-4 bg-white rounded-lg border border-rose-100 text-gray-800 italic text-sm">
                        "{{ $order->card_message ?: 'Khách hàng không yêu cầu viết thiệp.' }}"
                    </div>
                    @if($order->order_note)
                        <div class="mt-3 text-xs text-rose-800">
                            <b>Ghi chú giao hàng:</b> {{ $order->order_note }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- Cột phải: Thông tin giao nhận & Cập nhật trạng thái đơn -->
            <div class="space-y-6">
                <!-- Cập nhật trạng thái -->
                <div class="bg-white rounded-xl shadow-md p-6 border border-gray-200">
                    <h3 class="font-bold text-gray-800 text-base mb-4 pb-2 border-b">🔄 Cập nhật tiến độ cắm & giao</h3>
                    <form action="{{ route('admin.orders.update-status', $order) }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PATCH')
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Trạng thái đơn</label>
                            <select name="status"
                                class="w-full rounded-lg border border-gray-300 p-2.5 text-sm font-semibold">
                                <option value="PENDING" {{ $order->status == 'PENDING' ? 'selected' : '' }}>Chờ xử lý
                                    (PENDING)</option>
                                <option value="CONFIRMED" {{ $order->status == 'CONFIRMED' ? 'selected' : '' }}>Đã duyệt
                                    (CONFIRMED)</option>
                                <option value="PREPARING" {{ $order->status == 'PREPARING' ? 'selected' : '' }}>Đang bó / cắm
                                    hoa (PREPARING)</option>
                                <option value="SHIPPING" {{ $order->status == 'SHIPPING' ? 'selected' : '' }}>Đang giao hoa
                                    (SHIPPING)</option>
                                <option value="COMPLETED" {{ $order->status == 'COMPLETED' ? 'selected' : '' }}>Giao thành
                                    công (COMPLETED)</option>
                                <option value="CANCELLED" {{ $order->status == 'CANCELLED' ? 'selected' : '' }}>Hủy đơn
                                    (CANCELLED)</option>
                            </select>
                        </div>
                        <button type="submit" style="background-color: #e11d48; color: #ffffff;"
                            class="w-full py-2.5 font-bold rounded-lg shadow hover:opacity-90 text-sm">
                            Lưu trạng thái mới
                        </button>
                    </form>
                </div>

                <!-- Người nhận & Khung giờ -->
                <div class="bg-white rounded-xl shadow-md p-6 border border-gray-200 text-sm space-y-3">
                    <h3 class="font-bold text-gray-800 text-base pb-2 border-b">📍 Thông tin nhận hoa</h3>
                    <div>
                        <span class="text-xs text-gray-500 block">Người nhận</span>
                        <span class="font-bold text-gray-900">{{ $order->recipient_name }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-gray-500 block">Số điện thoại liên hệ</span>
                        <span class="font-semibold text-gray-900">{{ $order->recipient_phone }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-gray-500 block">Địa chỉ giao tận nơi</span>
                        <span class="text-gray-800">{{ $order->recipient_address }}</span>
                    </div>
                    <div class="pt-2 border-t">
                        <span class="text-xs text-gray-500 block">Ngày hẹn giao</span>
                        <span
                            class="font-bold text-rose-600">{{ \Carbon\Carbon::parse($order->delivery_date)->format('d/m/Y') }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-gray-500 block">Khung giờ giao đã hẹn</span>
                        <span
                            class="font-semibold text-purple-700">{{ $order->deliverySlot->time_range ?? 'Tiêu chuẩn' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection