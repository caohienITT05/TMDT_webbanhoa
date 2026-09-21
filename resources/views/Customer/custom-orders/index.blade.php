@php
    $statusLabels = [
        'pending' => ['Chờ xử lý', 'bg-amber-50 text-amber-800 border-amber-200'],
        'contacted' => ['Đã liên hệ', 'bg-sky-50 text-sky-800 border-sky-200'],
        'accepted' => ['Shop nhận yêu cầu', 'bg-emerald-50 text-emerald-800 border-emerald-200'],
        'rejected' => ['Shop từ chối', 'bg-red-50 text-red-800 border-red-200'],
        'converted' => ['Đã tạo đơn', 'bg-violet-50 text-violet-800 border-violet-200'],
    ];
@endphp

<x-customer.layout title="Yêu cầu của tôi">
    <x-customer.breadcrumb :items="[
        ['label' => 'Trang chủ', 'url' => route('home')],
        ['label' => 'Yêu cầu của tôi'],
    ]" />

    <section class="bloom-shell pb-14 sm:pb-20">
        <div class="flex flex-col gap-4 border-b border-bloom-line pb-7 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="bloom-eyebrow">Tài khoản</p>
                <h1 class="bloom-title mt-2">Yêu cầu đặt hoa của tôi</h1>
                <p class="bloom-subtitle mt-3">Theo dõi phản hồi của BloomGift cho các mẫu hoa bạn đã gửi.</p>
            </div>
            <a href="{{ route('custom.order') }}" class="bloom-button bloom-button--ghost">Gửi yêu cầu mới <x-customer.icon name="arrow-right" class="h-4 w-4" /></a>
        </div>

        <div class="mt-7 space-y-4">
            @forelse ($customOrderRequests as $customOrderRequest)
                @php([$statusLabel, $statusClass] = $statusLabels[$customOrderRequest->status] ?? ['Chưa cập nhật', 'bg-gray-50 text-gray-700 border-gray-200'])
                <article class="rounded-xl border border-bloom-line bg-white p-5 sm:p-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-sm font-bold text-bloom-ink">{{ $customOrderRequest->request_code }}</p>
                            <p class="mt-1 text-xs text-bloom-muted">Gửi {{ $customOrderRequest->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <span class="inline-flex w-fit rounded-full border px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
                    </div>
                    <dl class="mt-5 grid gap-3 text-sm sm:grid-cols-3">
                        <div><dt class="text-bloom-muted">Dịp tặng</dt><dd class="mt-1 font-semibold text-bloom-ink">{{ $customOrderRequest->occasion ?: '—' }}</dd></div>
                        <div><dt class="text-bloom-muted">Ngân sách</dt><dd class="mt-1 font-semibold text-bloom-ink">{{ $customOrderRequest->budget !== null ? number_format($customOrderRequest->budget, 0, ',', '.') . '₫' : '—' }}</dd></div>
                        <div><dt class="text-bloom-muted">Ngày giao</dt><dd class="mt-1 font-semibold text-bloom-ink">{{ $customOrderRequest->delivery_date?->format('d/m/Y') ?? '—' }}</dd></div>
                    </dl>
                    @if ($customOrderRequest->status === 'rejected' && $customOrderRequest->rejection_reason)
                        <p class="mt-4 rounded-lg border border-red-100 bg-red-50 px-3 py-2 text-sm text-red-800">Lý do từ chối: {{ $customOrderRequest->rejection_reason }}</p>
                    @endif
                    <div class="mt-5 flex flex-wrap gap-3">
                        <a href="{{ route('customer.custom-orders.show', $customOrderRequest) }}" class="bloom-button bloom-button--ghost bloom-button--small">Chi tiết <x-customer.icon name="arrow-right" class="h-4 w-4" /></a>
                        @if ($customOrderRequest->status === 'converted' && $customOrderRequest->order)
                            <a href="{{ route('orders.show', $customOrderRequest->order) }}" class="bloom-button bloom-button--small">Xem đơn hàng <x-customer.icon name="package" class="h-4 w-4" /></a>
                        @endif
                    </div>
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-bloom-line bg-white px-5 py-12 text-center">
                    <x-customer.icon name="clock" class="mx-auto h-8 w-8 text-bloom-rose" />
                    <h2 class="mt-4 font-display text-xl font-semibold text-bloom-plum">Chưa có yêu cầu nào</h2>
                    <p class="mt-2 text-sm text-bloom-muted">Khi bạn gửi yêu cầu đặt hoa, BloomGift sẽ cập nhật tiến trình ở đây.</p>
                </div>
            @endforelse
        </div>

        @if ($customOrderRequests->hasPages())
            <div class="mt-6">{{ $customOrderRequests->links() }}</div>
        @endif
    </section>
</x-customer.layout>
