@extends('layouts.admin')

@section('title', 'Chi tiết đơn hàng')

@section('content')
    @php
        $currentStatus = strtolower($order->status ?? $order->order_status ?? 'pending');
        $amount = $order->total ?? $order->total_amount ?? 0;
        $paymentMethods = [
            'paypal' => 'PayPal',
            'cod' => 'Thanh toán khi nhận hàng (COD)',
        ];
        $items = $order->orderItems?->isNotEmpty() ? $order->orderItems : ($order->items ?? collect());
    @endphp

    <div class="admin-page mx-auto max-w-6xl space-y-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-rose-500">Đơn hàng</p>
                <h1 class="admin-page-title mt-1">{{ $order->order_code ?? ('BG-' . $order->id) }}</h1>
                <p class="admin-page-subtitle">Chi tiết nhận hàng, thanh toán và tiến độ thực hiện đơn.</p>
            </div>
            <x-admin.button :href="route('admin.orders.index')" variant="secondary">
                <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m11.5 15-5-5 5-5" stroke-linecap="round" stroke-linejoin="round" /></svg>
                Quay lại danh sách
            </x-admin.button>
        </div>

        @if(session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="status">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800" role="alert">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
            <section class="admin-panel admin-panel--padded xl:col-span-2">
                <div class="mb-5 flex flex-wrap items-start justify-between gap-3 border-b border-rose-50 pb-4">
                    <div>
                        <h2 class="text-base font-bold text-gray-900">Thông tin giao hàng</h2>
                        <p class="mt-1 text-xs text-gray-500">Thông tin người nhận và lịch hẹn giao hoa.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <x-admin.status-badge :status="$currentStatus" />
                        <x-admin.status-badge :status="$order->payment_status" type="payment" />
                    </div>
                </div>

                <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-4">
                        <dt class="text-xs font-semibold text-gray-500">Người nhận</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $order->recipient_name ?? $order->user?->name ?? '—' }}</dd>
                    </div>
                    <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-4">
                        <dt class="text-xs font-semibold text-gray-500">Số điện thoại</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $order->recipient_phone ?? '—' }}</dd>
                    </div>
                    <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-4 sm:col-span-2">
                        <dt class="text-xs font-semibold text-gray-500">Địa chỉ giao hàng</dt>
                        <dd class="mt-1 text-sm font-semibold leading-6 text-gray-900">{{ $order->recipient_address ?? 'Chưa cập nhật' }}</dd>
                    </div>
                    <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-4">
                        <dt class="text-xs font-semibold text-gray-500">Ngày giao</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $order->delivery_date?->format('d/m/Y') ?? 'Chưa chọn' }}</dd>
                    </div>
                    <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-4">
                        <dt class="text-xs font-semibold text-gray-500">Khung giờ giao</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $order->deliverySlot?->name ?? $order->deliverySlot?->time_range ?? 'Tiêu chuẩn' }}</dd>
                    </div>
                </dl>

                @if($order->note)
                    <div class="mt-4 rounded-xl border border-rose-100 bg-rose-50/50 p-4">
                        <p class="text-xs font-semibold text-rose-800">Lời chúc thiệp & ghi chú</p>
                        <p class="mt-1.5 text-sm leading-6 text-gray-700">{{ $order->note }}</p>
                    </div>
                @endif
            </section>

            <aside class="admin-panel admin-panel--padded h-fit">
                <h2 class="text-base font-bold text-gray-900">Cập nhật trạng thái</h2>
                <p class="mt-1 text-xs leading-5 text-gray-500">Thay đổi tiến độ thực hiện của đơn hàng này.</p>

                <form method="POST" action="{{ route('admin.orders.update-status', $order) }}" class="mt-4 space-y-3">
                    @csrf
                    @method('PATCH')
                    <label for="order_status" class="block text-xs font-semibold text-gray-700">Trạng thái đơn hàng</label>
                    <select id="order_status" name="order_status" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                        <option value="pending" @selected($currentStatus === 'pending')>Chờ xác nhận</option>
                        <option value="confirmed" @selected($currentStatus === 'confirmed')>Đã xác nhận</option>
                        <option value="preparing" @selected(in_array($currentStatus, ['preparing', 'processing']))>Đang chuẩn bị</option>
                        <option value="shipping" @selected($currentStatus === 'shipping')>Đang giao</option>
                        <option value="completed" @selected(in_array($currentStatus, ['completed', 'delivered']))>Hoàn thành</option>
                        <option value="cancelled" @selected(in_array($currentStatus, ['cancelled', 'canceled']))>Đã hủy</option>
                    </select>
                    <x-admin.button type="submit" class="w-full" variant="primary">
                        Cập nhật trạng thái
                    </x-admin.button>
                </form>
            </aside>
        </div>

        <section class="admin-panel overflow-hidden">
            <div class="border-b border-rose-50 px-5 py-4">
                <h2 class="text-base font-bold text-gray-900">Sản phẩm trong đơn</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[620px] text-left text-sm text-gray-600">
                    <thead class="bg-rose-50/60 text-[11px] font-bold uppercase tracking-wide text-rose-900">
                        <tr>
                            <th class="px-5 py-3">Sản phẩm</th>
                            <th class="px-5 py-3 text-right">Đơn giá</th>
                            <th class="px-5 py-3 text-center">Số lượng</th>
                            <th class="px-5 py-3 text-right">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-rose-50">
                        @forelse($items as $item)
                            @php $lineTotal = $item->subtotal ?? ($item->price * $item->quantity); @endphp
                            <tr>
                                <td class="px-5 py-4 font-medium text-gray-900">{{ $item->product_name ?? $item->product?->name ?? 'Bó hoa tươi' }}</td>
                                <td class="px-5 py-4 text-right">{{ number_format($item->price ?? 0, 0, ',', '.') }} đ</td>
                                <td class="px-5 py-4 text-center">{{ $item->quantity }}</td>
                                <td class="px-5 py-4 text-right font-semibold text-gray-900">{{ number_format($lineTotal, 0, ',', '.') }} đ</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-10 text-center text-sm text-gray-400">Không có sản phẩm trong đơn hàng.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="ml-auto w-full max-w-sm rounded-xl border border-rose-100 bg-white p-5 shadow-sm">
            <div class="flex justify-between gap-4 text-sm text-gray-600"><span>Tạm tính</span><span>{{ number_format($order->subtotal ?? 0, 0, ',', '.') }} đ</span></div>
            @if(($order->discount ?? 0) > 0)
                <div class="mt-2 flex justify-between gap-4 text-sm text-emerald-700"><span>Ưu đãi</span><span>-{{ number_format($order->discount, 0, ',', '.') }} đ</span></div>
            @endif
            <div class="mt-2 flex justify-between gap-4 text-sm text-gray-600"><span>Phí giao hàng</span><span>{{ number_format($order->shipping_fee ?? 0, 0, ',', '.') }} đ</span></div>
            <div class="mt-4 flex justify-between gap-4 border-t border-rose-100 pt-4 text-base font-bold text-gray-900"><span>Tổng thanh toán</span><span class="text-rose-600">{{ number_format($amount, 0, ',', '.') }} đ</span></div>
        </div>

        @if($order->payment && $order->payment_method === 'paypal')
            <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-xs text-gray-600">
                Giao dịch PayPal: <span class="font-semibold text-gray-900">{{ $order->payment->transaction_id ?? 'Chưa có mã giao dịch' }}</span>
            </div>
        @endif
    </div>
@endsection
