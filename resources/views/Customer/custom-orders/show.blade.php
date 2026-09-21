@php
    $statusLabels = [
        'pending' => ['Chờ xử lý', 'bg-amber-50 text-amber-800 border-amber-200'],
        'contacted' => ['Đã liên hệ', 'bg-sky-50 text-sky-800 border-sky-200'],
        'accepted' => ['Shop nhận yêu cầu', 'bg-emerald-50 text-emerald-800 border-emerald-200'],
        'rejected' => ['Shop từ chối', 'bg-red-50 text-red-800 border-red-200'],
        'converted' => ['Đã tạo đơn', 'bg-violet-50 text-violet-800 border-violet-200'],
    ];
    [$statusLabel, $statusClass] = $statusLabels[$customOrderRequest->status] ?? ['Chưa cập nhật', 'bg-gray-50 text-gray-700 border-gray-200'];
@endphp

<x-customer.layout :title="$customOrderRequest->request_code">
    <x-customer.breadcrumb :items="[
        ['label' => 'Trang chủ', 'url' => route('home')],
        ['label' => 'Yêu cầu của tôi', 'url' => route('customer.custom-orders.index')],
        ['label' => $customOrderRequest->request_code],
    ]" />

    <section class="bloom-shell pb-14 sm:pb-20">
        <div class="mx-auto max-w-3xl">
            <div class="flex flex-col gap-4 border-b border-bloom-line pb-7 sm:flex-row sm:items-start sm:justify-between">
                <div><p class="bloom-eyebrow">Yêu cầu đặt hoa</p><h1 class="bloom-title mt-2">{{ $customOrderRequest->request_code }}</h1><p class="bloom-subtitle mt-3">Gửi lúc {{ $customOrderRequest->created_at->format('d/m/Y H:i') }}</p></div>
                <span class="inline-flex w-fit rounded-full border px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
            </div>

            @if ($customOrderRequest->status === 'rejected' && $customOrderRequest->rejection_reason)
                <div class="mt-6 rounded-xl border border-red-100 bg-red-50 p-4 text-sm text-red-800"><p class="font-semibold">Lý do từ chối</p><p class="mt-1 leading-6">{{ $customOrderRequest->rejection_reason }}</p></div>
            @endif
            @if ($customOrderRequest->status === 'accepted' && $customOrderRequest->admin_note)
                <div class="mt-6 rounded-xl border border-emerald-100 bg-emerald-50 p-4 text-sm text-emerald-800"><p class="font-semibold">Phản hồi từ BloomGift</p><p class="mt-1 leading-6">{{ $customOrderRequest->admin_note }}</p></div>
            @endif

            <div class="mt-6 rounded-xl border border-bloom-line bg-white p-5 sm:p-6">
                <dl class="grid gap-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-bloom-muted">Họ tên</dt><dd class="mt-1 font-semibold text-bloom-ink">{{ $customOrderRequest->customer_name }}</dd></div>
                    <div><dt class="text-bloom-muted">Số điện thoại</dt><dd class="mt-1 font-semibold text-bloom-ink">{{ $customOrderRequest->phone }}</dd></div>
                    <div><dt class="text-bloom-muted">Email</dt><dd class="mt-1 font-semibold text-bloom-ink">{{ $customOrderRequest->email ?: '—' }}</dd></div>
                    <div><dt class="text-bloom-muted">Dịp tặng</dt><dd class="mt-1 font-semibold text-bloom-ink">{{ $customOrderRequest->occasion ?: '—' }}</dd></div>
                    <div><dt class="text-bloom-muted">Loại hoa</dt><dd class="mt-1 font-semibold text-bloom-ink">{{ $customOrderRequest->flower_type ?: '—' }}</dd></div>
                    <div><dt class="text-bloom-muted">Số lượng</dt><dd class="mt-1 font-semibold text-bloom-ink">{{ $customOrderRequest->quantity ?: '—' }}</dd></div>
                    <div><dt class="text-bloom-muted">Ngân sách</dt><dd class="mt-1 font-semibold text-bloom-ink">{{ $customOrderRequest->budget !== null ? number_format($customOrderRequest->budget, 0, ',', '.') . '₫' : '—' }}</dd></div>
                    <div><dt class="text-bloom-muted">Ngày giao</dt><dd class="mt-1 font-semibold text-bloom-ink">{{ $customOrderRequest->delivery_date?->format('d/m/Y') ?? '—' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-bloom-muted">Địa chỉ giao</dt><dd class="mt-1 font-semibold leading-6 text-bloom-ink">{{ $customOrderRequest->delivery_address ?: '—' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-bloom-muted">Nội dung yêu cầu</dt><dd class="mt-1 whitespace-pre-line font-semibold leading-6 text-bloom-ink">{{ $customOrderRequest->message ?: '—' }}</dd></div>
                </dl>
            </div>

            <div class="mt-6 flex flex-wrap gap-3"><a href="{{ route('customer.custom-orders.index') }}" class="bloom-button bloom-button--ghost"><x-customer.icon name="arrow-left" class="h-4 w-4" />Quay lại</a>@if ($customOrderRequest->status === 'converted' && $customOrderRequest->order)<a href="{{ route('orders.show', $customOrderRequest->order) }}" class="bloom-button">Xem đơn hàng <x-customer.icon name="package" class="h-4 w-4" /></a>@endif</div>
        </div>
    </section>
</x-customer.layout>
