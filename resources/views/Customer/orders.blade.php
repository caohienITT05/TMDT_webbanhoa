<x-customer.layout title="Đơn hàng của tôi">
    <x-customer.breadcrumb :items="[
        ['label' => 'Trang chủ', 'url' => route('home')],
        ['label' => 'Đơn hàng của tôi'],
    ]" />

    <section class="bloom-shell pb-14 sm:pb-20">
        <div class="flex flex-wrap items-end justify-between gap-4 border-b border-bloom-line pb-7">
            <div>
                <p class="bloom-eyebrow">Tài khoản BloomGift</p>
                <h1 class="bloom-title mt-2">Đơn hàng của tôi</h1>
                <p class="bloom-subtitle mt-3">Theo dõi trạng thái đơn, thông tin nhận hoa và thanh toán của bạn.</p>
            </div>
            <a href="{{ route('products.index') }}" class="bloom-button bloom-button--ghost bloom-button--small">Tiếp tục mua sắm</a>
        </div>

        @if ($orders->isEmpty())
            <div class="bloom-empty mt-8">
                <span class="flex h-14 w-14 items-center justify-center rounded-full bg-bloom-blush text-bloom-rose"><x-customer.icon name="receipt" class="h-6 w-6" /></span>
                <h2 class="mt-4 font-display text-xl font-semibold text-bloom-plum">Bạn chưa có đơn hàng nào</h2>
                <p class="mt-2 max-w-md text-sm leading-6 text-bloom-muted">Khám phá những sản phẩm đang có để bắt đầu một đơn hoa thật ý nghĩa.</p>
                <a href="{{ route('products.index') }}" class="bloom-button mt-5">Khám phá sản phẩm</a>
            </div>
        @else
            <div class="mt-8 space-y-4">
                @foreach ($orders as $order)
                    @php
                        $orderStatus = $order->order_status ?? $order->status ?? 'pending';
                        $orderTotal = $order->total ?? $order->total_amount ?? 0;
                        $items = $order->orderItems;
                    @endphp
                    <article class="bloom-panel overflow-hidden">
                        <div class="flex flex-col gap-3 border-b border-bloom-line bg-bloom-blush/55 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-display text-lg font-semibold text-bloom-plum">{{ $order->order_code ?? ('BG-' . $order->id) }}</p>
                                <p class="mt-0.5 text-xs text-bloom-muted">Đặt ngày {{ $order->created_at?->format('d/m/Y · H:i') }}</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <x-customer.status-badge :status="$orderStatus" />
                                <x-customer.status-badge :status="($order->payment_status ?? 'pending')" type="payment" />
                            </div>
                        </div>
                        <div class="grid gap-5 p-5 lg:grid-cols-[1fr_auto] lg:items-end">
                            <div class="grid gap-5 sm:grid-cols-2">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[.1em] text-bloom-muted">Người nhận</p>
                                    <p class="mt-1.5 text-sm font-semibold text-bloom-ink">{{ $order->recipient_name ?: 'Chưa cập nhật' }}</p>
                                    <p class="mt-1 text-sm leading-5 text-bloom-muted">{{ $order->recipient_phone }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[.1em] text-bloom-muted">Lịch giao</p>
                                    <p class="mt-1.5 text-sm font-semibold text-bloom-ink">{{ $order->delivery_date ? \Carbon\Carbon::parse($order->delivery_date)->format('d/m/Y') : 'Chưa chọn ngày' }}</p>
                                    <p class="mt-1 text-sm leading-5 text-bloom-muted">{{ $order->deliverySlot?->name ?: 'Chưa chọn khung giờ' }}</p>
                                </div>
                                <div class="sm:col-span-2">
                                    <p class="text-xs font-bold uppercase tracking-[.1em] text-bloom-muted">Sản phẩm</p>
                                    <p class="mt-1.5 text-sm leading-6 text-bloom-muted">
                                        @forelse ($items->take(2) as $item)
                                            <span class="text-bloom-ink">{{ $item->product_name ?? $item->product?->name ?? 'Hoa tươi' }}</span> × {{ $item->quantity }}@if (! $loop->last), @endif
                                        @empty
                                            Chưa có sản phẩm hiển thị
                                        @endforelse
                                        @if ($items->count() > 2) <span> và {{ $items->count() - 2 }} sản phẩm khác</span> @endif
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-col items-start gap-3 border-t border-bloom-line pt-4 sm:flex-row sm:items-center sm:justify-between lg:block lg:border-l lg:border-t-0 lg:pl-6 lg:pt-0">
                                <div class="lg:text-right">
                                    <p class="text-xs font-bold uppercase tracking-[.1em] text-bloom-muted">Tổng thanh toán</p>
                                    <p class="mt-1 text-xl font-bold text-bloom-rose">{{ number_format((float) $orderTotal, 0, ',', '.') }}₫</p>
                                    <p class="mt-1 text-xs text-bloom-muted">{{ $order->payment_method === 'paypal' ? 'PayPal' : 'COD' }}</p>
                                </div>
                                <a href="{{ route('orders.show', $order) }}" class="bloom-button bloom-button--ghost bloom-button--small lg:mt-4">Xem chi tiết <x-customer.icon name="arrow-right" class="h-4 w-4" /></a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            @if ($orders->hasPages())
                <div class="mt-10 border-t border-bloom-line pt-6">{{ $orders->onEachSide(1)->links() }}</div>
            @endif
        @endif
    </section>
</x-customer.layout>
