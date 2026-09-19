@extends('layouts.admin')

@section('title', 'Quản lý đơn hàng')

@section('content')
    <div class="admin-page space-y-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-rose-500">Vận hành</p>
                <h1 class="admin-page-title mt-1">Quản lý đơn hàng</h1>
                <p class="admin-page-subtitle">Theo dõi tiến độ giao hoa và trạng thái thanh toán của từng đơn.</p>
            </div>
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

        <div class="admin-panel overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm text-gray-600">
                    <thead class="border-b border-rose-100 bg-rose-50/70 text-[11px] font-bold uppercase tracking-wide text-rose-900">
                        <tr>
                            <th class="px-5 py-3.5">Mã đơn</th>
                            <th class="px-5 py-3.5">Khách hàng</th>
                            <th class="px-5 py-3.5">Ngày giao</th>
                            <th class="px-5 py-3.5">Tổng tiền</th>
                            <th class="px-5 py-3.5">Thanh toán</th>
                            <th class="px-5 py-3.5">Trạng thái</th>
                            <th class="px-5 py-3.5 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-rose-50">
                        @forelse($orders as $order)
                            @php
                                $orderStatus = $order->status ?? $order->order_status ?? 'pending';
                                $amount = $order->total ?? $order->total_amount ?? 0;
                            @endphp
                            <tr class="transition-colors hover:bg-rose-50/35">
                                <td class="px-5 py-4 font-semibold text-gray-900">
                                    {{ $order->order_code ?? ('BG-' . $order->id) }}
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-medium text-gray-800">{{ $order->recipient_name ?? $order->user?->name ?? '—' }}</p>
                                    <p class="mt-0.5 text-xs text-gray-400">{{ $order->recipient_phone ?? 'Chưa có SĐT' }}</p>
                                </td>
                                <td class="px-5 py-4 text-sm">
                                    {{ $order->delivery_date?->format('d/m/Y') ?? 'Chưa chọn' }}
                                </td>
                                <td class="px-5 py-4 font-semibold text-gray-900">
                                    {{ number_format($amount, 0, ',', '.') }} đ
                                </td>
                                <td class="px-5 py-4">
                                    <x-admin.status-badge :status="$order->payment_status" type="payment" />
                                </td>
                                <td class="px-5 py-4">
                                    <x-admin.status-badge :status="$orderStatus" />
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <x-admin.button :href="route('admin.orders.show', $order)" variant="detail" size="sm">
                                        <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10 3.75c-4.25 0-7.25 4.1-7.25 6.25S5.75 16.25 10 16.25 17.25 12.15 17.25 10 14.25 3.75 10 3.75Z" /><circle cx="10" cy="10" r="2.15" /></svg>
                                        Chi tiết
                                    </x-admin.button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center text-sm text-gray-400">Chưa có đơn hàng.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-rose-50 px-5 py-4">
                <x-admin.pagination :paginator="$orders" item-label="đơn hàng" />
            </div>
        </div>
    </div>
@endsection
