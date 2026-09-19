<x-customer.layout :title="'Đơn hàng ' . ($order->order_code ?? ('BG-' . $order->id))">
    @php
        $status = strtolower($order->order_status ?? $order->status ?? 'pending');
        $paymentStatus = $order->payment_status ?? 'pending';
        $steps = ['pending' => 1, 'confirmed' => 2, 'preparing' => 3, 'processing' => 3, 'shipping' => 4, 'delivered' => 5, 'completed' => 5];
        $currentStep = $steps[$status] ?? 1;
        $isCancelled = $status === 'cancelled';
        $items = $order->orderItems->isNotEmpty() ? $order->orderItems : $order->items;
        $total = $order->total ?? $order->total_amount ?? 0;
    @endphp

    <x-customer.breadcrumb :items="[
        ['label' => 'Trang chủ', 'url' => route('home')],
        ['label' => 'Đơn hàng của tôi', 'url' => route('customer.orders')],
        ['label' => $order->order_code ?? ('BG-' . $order->id)],
    ]" />

    <section class="bloom-shell pb-14 sm:pb-20">
        <div class="flex flex-col gap-5 border-b border-bloom-line pb-7 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="bloom-eyebrow">Thông tin đơn hàng</p>
                <h1 class="bloom-title mt-2">{{ $order->order_code ?? ('BG-' . $order->id) }}</h1>
                <p class="bloom-subtitle mt-3">Đặt ngày {{ $order->created_at?->format('d/m/Y · H:i') }}</p>
            </div>
            <div class="flex flex-wrap gap-2"><x-customer.status-badge :status="$status" /><x-customer.status-badge :status="$paymentStatus" type="payment" /></div>
        </div>

        @if (! $isCancelled)
            <section class="bloom-panel mt-7 overflow-hidden">
                <div class="border-b border-bloom-line px-5 py-4 sm:px-6"><h2 class="font-display text-lg font-semibold text-bloom-plum">Tiến trình đơn hàng</h2></div>
                <div class="grid grid-cols-5 gap-1 p-5 sm:gap-2 sm:p-6">
                    @foreach (['Tiếp nhận', 'Xác nhận', 'Chuẩn bị', 'Đang giao', 'Hoàn tất'] as $index => $label)
                        @php $stepNumber = $index + 1; $isActive = $currentStep >= $stepNumber; @endphp
                        <div class="relative text-center {{ $index < 4 ? 'after:absolute after:left-[58%] after:right-[-42%] after:top-4 after:h-px after:bg-bloom-line' : '' }} {{ $isActive && $index < 4 ? 'after:!bg-bloom-rose' : '' }}">
                            <span class="relative z-10 mx-auto flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold {{ $isActive ? 'bg-bloom-rose text-white' : 'bg-bloom-blush text-bloom-muted' }}">{{ $stepNumber }}</span>
                            <p class="mt-2 text-[10px] font-semibold leading-4 sm:text-xs {{ $isActive ? 'text-bloom-rose' : 'text-bloom-muted' }}">{{ $label }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <div class="mt-7 grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start">
            <div class="space-y-5">
                <section class="bloom-panel bloom-panel--padded">
                    <h2 class="bloom-card-title">Sản phẩm trong đơn</h2>
                    <div class="mt-5 divide-y divide-bloom-line">
                        @forelse ($items as $item)
                            <div class="flex gap-4 py-4 first:pt-0 last:pb-0">
                                <div class="h-18 w-18 shrink-0 overflow-hidden rounded-lg bg-bloom-blush">
                                    <x-customer.product-image :product="$item->product" class="h-full w-full" :alt="$item->product_name ?? 'Sản phẩm BloomGift'" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    @if ($item->product)
                                        <a href="{{ route('product.detail', $item->product->slug ?: $item->product->id) }}" class="font-semibold text-bloom-ink transition hover:text-bloom-rose">{{ $item->product_name ?? $item->product->name }}</a>
                                    @else
                                        <p class="font-semibold text-bloom-ink">{{ $item->product_name ?? 'Hoa tươi' }}</p>
                                    @endif
                                    <p class="mt-1 text-sm text-bloom-muted">{{ number_format((float) $item->price, 0, ',', '.') }}₫ × {{ $item->quantity }}</p>
                                </div>
                                <p class="shrink-0 text-right text-sm font-bold text-bloom-rose">{{ number_format((float) ($item->subtotal ?? ((float) $item->price * (int) $item->quantity)), 0, ',', '.') }}₫</p>
                            </div>
                        @empty
                            <p class="py-4 text-sm text-bloom-muted">Không có sản phẩm hiển thị trong đơn hàng này.</p>
                        @endforelse
                    </div>
                </section>

                <section class="bloom-panel bloom-panel--padded">
                    <h2 class="bloom-card-title">Thông tin giao nhận</h2>
                    <div class="mt-5 grid gap-5 sm:grid-cols-2">
                        <div><p class="text-xs font-bold uppercase tracking-[.1em] text-bloom-muted">Người nhận</p><p class="mt-2 text-sm font-semibold text-bloom-ink">{{ $order->recipient_name ?: 'Chưa cập nhật' }}</p><p class="mt-1 text-sm text-bloom-muted">{{ $order->recipient_phone ?: 'Chưa cập nhật' }}</p></div>
                        <div><p class="text-xs font-bold uppercase tracking-[.1em] text-bloom-muted">Thời gian giao</p><p class="mt-2 text-sm font-semibold text-bloom-ink">{{ $order->delivery_date ? \Carbon\Carbon::parse($order->delivery_date)->format('d/m/Y') : 'Chưa chọn ngày' }}</p><p class="mt-1 text-sm text-bloom-muted">{{ $order->deliverySlot?->name ?: 'Chưa chọn khung giờ' }}</p></div>
                        <div class="sm:col-span-2"><p class="text-xs font-bold uppercase tracking-[.1em] text-bloom-muted">Địa chỉ</p><p class="mt-2 text-sm leading-6 text-bloom-ink">{{ $order->recipient_address ?: 'Chưa cập nhật' }}</p></div>
                    </div>
                </section>

                @if ($order->note)
                    <section class="rounded-xl border border-bloom-line bg-bloom-blush p-5 sm:p-6">
                        <div class="flex gap-3"><x-customer.icon name="gift" class="mt-0.5 h-5 w-5 shrink-0 text-bloom-rose" /><div><h2 class="font-semibold text-bloom-ink">Lời nhắn & ghi chú</h2><p class="mt-2 whitespace-pre-line text-sm leading-6 text-bloom-muted">{{ $order->note }}</p></div></div>
                    </section>
                @endif
            </div>

            <aside class="space-y-5 lg:sticky lg:top-28">
                <section class="bloom-panel bloom-panel--padded">
                    <h2 class="font-display text-lg font-semibold text-bloom-plum">Thanh toán</h2>
                    <div class="mt-5 space-y-3 text-sm">
                        <div class="flex justify-between gap-3"><span class="text-bloom-muted">Phương thức</span><span class="font-semibold text-bloom-ink">{{ $order->payment_method === 'paypal' ? 'PayPal' : 'COD' }}</span></div>
                        <div class="flex justify-between gap-3"><span class="text-bloom-muted">Trạng thái</span><x-customer.status-badge :status="$paymentStatus" type="payment" /></div>
                        @if ($order->payment?->transaction_id)
                            <div class="border-t border-bloom-line pt-3"><p class="text-xs font-bold uppercase tracking-[.1em] text-bloom-muted">Mã giao dịch</p><p class="mt-1 break-all text-xs leading-5 text-bloom-ink">{{ $order->payment->transaction_id }}</p></div>
                        @endif
                    </div>
                </section>

                <section class="bloom-panel bloom-panel--padded">
                    <h2 class="font-display text-lg font-semibold text-bloom-plum">Tổng kết đơn hàng</h2>
                    <div class="mt-5 space-y-3 border-b border-bloom-line pb-5 text-sm">
                        <div class="flex justify-between gap-3"><span class="text-bloom-muted">Tạm tính</span><span class="font-semibold text-bloom-ink">{{ number_format((float) $order->subtotal, 0, ',', '.') }}₫</span></div>
                        @if ((float) $order->discount > 0)
                            <div class="flex justify-between gap-3"><span class="text-bloom-muted">Giảm giá</span><span class="font-semibold text-emerald-700">-{{ number_format((float) $order->discount, 0, ',', '.') }}₫</span></div>
                        @endif
                        <div class="flex justify-between gap-3"><span class="text-bloom-muted">Phí giao hàng</span><span class="font-semibold text-bloom-ink">{{ number_format((float) $order->shipping_fee, 0, ',', '.') }}₫</span></div>
                    </div>
                    <div class="mt-5 flex items-end justify-between gap-3"><span class="font-semibold text-bloom-ink">Tổng thanh toán</span><span class="text-xl font-bold text-bloom-rose">{{ number_format((float) $total, 0, ',', '.') }}₫</span></div>
                </section>
                <a href="{{ route('customer.orders') }}" class="bloom-button bloom-button--ghost w-full"><x-customer.icon name="arrow-left" class="h-4 w-4" />Quay lại đơn hàng</a>
            </aside>
        </div>
    </section>
</x-customer.layout>
