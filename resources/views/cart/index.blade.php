<x-customer.layout title="Giỏ hàng">
    <x-customer.breadcrumb :items="[
        ['label' => 'Trang chủ', 'url' => route('home')],
        ['label' => 'Giỏ hàng'],
    ]" />

    <section class="bloom-shell pb-14 sm:pb-20">
        <div class="flex flex-wrap items-end justify-between gap-4 border-b border-bloom-line pb-7">
            <div>
                <p class="bloom-eyebrow">Đơn hàng của bạn</p>
                <h1 class="bloom-title mt-2">Giỏ hàng</h1>
                <p class="bloom-subtitle mt-3">Kiểm tra sản phẩm và số lượng trước khi thanh toán.</p>
            </div>
            @if (! $cartItems->isEmpty())
                <a href="{{ route('products.index') }}" class="bloom-text-link inline-flex items-center gap-1"><x-customer.icon name="arrow-left" class="h-4 w-4" />Tiếp tục chọn hoa</a>
            @endif
        </div>

        @if ($cartItems->isEmpty())
            <div class="bloom-empty mt-8">
                <span class="flex h-14 w-14 items-center justify-center rounded-full bg-bloom-blush text-bloom-rose"><x-customer.icon name="bag" class="h-6 w-6" /></span>
                <h2 class="mt-4 font-display text-xl font-semibold text-bloom-plum">Giỏ hàng đang trống</h2>
                <p class="mt-2 max-w-md text-sm leading-6 text-bloom-muted">Hãy dạo quanh BloomGift và chọn những sản phẩm bạn muốn gửi trao.</p>
                <a href="{{ route('products.index') }}" class="bloom-button mt-5">Khám phá sản phẩm</a>
            </div>
        @else
            <div class="mt-8 grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start">
                <div class="space-y-3">
                    @foreach ($cartItems as $item)
                        @php
                            $itemProduct = $item->product;
                            $lineTotal = (float) ($item->calculated_subtotal ?? ((float) $item->price * (int) $item->quantity));
                        @endphp
                        <article class="bloom-panel p-4 sm:p-5">
                            <div class="grid gap-4 sm:grid-cols-[6.25rem_minmax(0,1fr)] sm:gap-5">
                                <div class="aspect-square overflow-hidden rounded-lg bg-bloom-blush">
                                    <x-customer.product-image :product="$itemProduct" class="h-full w-full" />
                                </div>
                                <div class="min-w-0">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            @if ($itemProduct)
                                                <a href="{{ route('product.detail', $itemProduct->slug ?: $itemProduct->id) }}" class="font-display text-lg font-semibold text-bloom-plum transition hover:text-bloom-rose">{{ $itemProduct->name }}</a>
                                                @if ($itemProduct->category)
                                                    <p class="mt-1 text-xs text-bloom-muted">{{ $itemProduct->category->name }}</p>
                                                @endif
                                            @else
                                                <p class="font-semibold text-bloom-danger">Sản phẩm không còn khả dụng (#{{ $item->product_id }})</p>
                                            @endif
                                        </div>
                                        <form action="{{ route('cart.remove', $item->id) }}" method="POST" onsubmit="return confirm('Xóa mẫu hoa này khỏi giỏ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="bloom-icon-button border border-bloom-line bg-white text-bloom-muted hover:text-bloom-danger" aria-label="Xóa sản phẩm"><x-customer.icon name="trash" class="h-4 w-4" /></button>
                                        </form>
                                    </div>
                                    <div class="mt-5 flex flex-wrap items-end justify-between gap-4 border-t border-bloom-line pt-4">
                                        <div>
                                            <p class="text-xs font-medium uppercase tracking-[.1em] text-bloom-muted">Đơn giá</p>
                                            <p class="mt-1 text-sm font-semibold text-bloom-ink">{{ number_format((float) $item->price, 0, ',', '.') }}₫</p>
                                        </div>
                                        <form action="{{ route('cart.update', $item->id) }}" method="POST" class="flex items-end gap-2" x-data="{ quantity: {{ (int) $item->quantity }} }">
                                            @csrf
                                            @method('PATCH')
                                            <div>
                                                <label class="text-xs font-medium uppercase tracking-[.1em] text-bloom-muted" for="quantity-{{ $item->id }}">Số lượng</label>
                                                <div class="bloom-quantity mt-1.5">
                                                    <button type="button" @click="quantity = Math.max(1, quantity - 1)" aria-label="Giảm số lượng"><x-customer.icon name="minus" class="h-4 w-4" /></button>
                                                    <input id="quantity-{{ $item->id }}" name="quantity" type="number" min="1" max="99" x-model.number="quantity" value="{{ $item->quantity }}">
                                                    <button type="button" @click="quantity = Math.min(99, quantity + 1)" aria-label="Tăng số lượng"><x-customer.icon name="plus" class="h-4 w-4" /></button>
                                                </div>
                                            </div>
                                            <button type="submit" class="bloom-button bloom-button--ghost bloom-button--small">Cập nhật</button>
                                        </form>
                                        <div class="text-right">
                                            <p class="text-xs font-medium uppercase tracking-[.1em] text-bloom-muted">Thành tiền</p>
                                            <p class="mt-1 text-base font-bold text-bloom-rose">{{ number_format($lineTotal, 0, ',', '.') }}₫</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <aside class="bloom-panel bloom-panel--padded sticky top-28">
                    <h2 class="font-display text-xl font-semibold text-bloom-plum">Tóm tắt đơn hàng</h2>
                    <div class="mt-5 space-y-3 border-y border-bloom-line py-5 text-sm">
                        <div class="flex justify-between gap-4"><span class="text-bloom-muted">Tạm tính ({{ $cartItems->sum('quantity') }} sản phẩm)</span><span class="font-semibold text-bloom-ink">{{ number_format($subtotal, 0, ',', '.') }}₫</span></div>
                        @if ((float) $discount > 0)
                            <div class="flex justify-between gap-4"><span class="text-bloom-muted">Giảm giá</span><span class="font-semibold text-emerald-700">-{{ number_format($discount, 0, ',', '.') }}₫</span></div>
                        @endif
                        @if ((float) $giftCardFee > 0)
                            <div class="flex justify-between gap-4"><span class="text-bloom-muted">Thiệp chúc mừng</span><span class="font-semibold text-bloom-ink">{{ number_format($giftCardFee, 0, ',', '.') }}₫</span></div>
                        @endif
                        @if ((float) $giftWrapFee > 0)
                            <div class="flex justify-between gap-4"><span class="text-bloom-muted">Gói quà</span><span class="font-semibold text-bloom-ink">{{ number_format($giftWrapFee, 0, ',', '.') }}₫</span></div>
                        @endif
                        @if ((float) $shippingFee > 0)
                            <div class="flex justify-between gap-4"><span class="text-bloom-muted">Phí giao hàng</span><span class="font-semibold text-bloom-ink">{{ number_format($shippingFee, 0, ',', '.') }}₫</span></div>
                        @endif
                    </div>
                    <div class="mt-5 flex items-end justify-between gap-4">
                        <span class="font-semibold text-bloom-ink">Tổng cộng</span>
                        <span class="text-xl font-bold text-bloom-rose">{{ number_format($total, 0, ',', '.') }}₫</span>
                    </div>
                    <p class="mt-3 text-xs leading-5 text-bloom-muted">Tùy chọn lời nhắn, gói quà, mã giảm giá và thông tin giao hàng sẽ được xác nhận tại bước thanh toán.</p>
                    <a href="{{ route('checkout.index') }}" class="bloom-button mt-5 w-full">Tiến hành thanh toán <x-customer.icon name="arrow-right" class="h-4 w-4" /></a>
                </aside>
            </div>
        @endif
    </section>
</x-customer.layout>
