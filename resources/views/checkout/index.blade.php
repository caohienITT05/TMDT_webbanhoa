@php
    $isBuyNow = ($checkoutMode ?? 'cart') === 'buy_now';
    $buyNowHasSale = $isBuyNow && (float) ($buyNowProduct?->sale_price ?? 0) > 0 && (float) $buyNowProduct->sale_price < (float) $buyNowProduct->price;
    $buyNowUnitPrice = $isBuyNow ? (float) ($buyNowHasSale ? $buyNowProduct->sale_price : $buyNowProduct?->price) : 0;
@endphp

<x-customer.layout :title="$isBuyNow ? 'Xác nhận đặt hàng' : 'Thanh toán'">
    <x-customer.breadcrumb :items="[
        ['label' => 'Trang chủ', 'url' => route('home')],
        ['label' => $isBuyNow ? 'Đặt hàng' : 'Giỏ hàng', 'url' => $isBuyNow ? route('buy-now.checkout') : route('cart.index')],
        ['label' => $isBuyNow ? 'Xác nhận đặt hàng' : 'Thanh toán'],
    ]" />

    <section class="bloom-shell pb-14 sm:pb-20">
        <div class="border-b border-bloom-line pb-7">
            <p class="bloom-eyebrow">{{ $isBuyNow ? 'Đặt hàng' : 'Hoàn tất đơn hàng' }}</p>
            <h1 class="bloom-title mt-2">{{ $isBuyNow ? 'Xác nhận đặt hàng' : 'Thanh toán' }}</h1>
            <p class="bloom-subtitle mt-3">{{ $isBuyNow ? 'Kiểm tra số lượng, thông tin giao nhận và thanh toán cho sản phẩm bạn chọn.' : 'Điền thông tin giao nhận, chọn các tùy chọn phù hợp và kiểm tra lại đơn hàng trước khi đặt.' }}</p>
        </div>

        @if ($errors->any())
            <div class="mt-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm leading-6 text-red-800">
                <p class="font-semibold">Thông tin đơn hàng cần được kiểm tra lại.</p>
                <p>{{ $errors->first() }}</p>
            </div>
        @endif

        <form action="{{ route('checkout.process') }}" method="POST" id="checkout-form" class="mt-8">
            @csrf
            <input type="hidden" name="checkout_mode" value="{{ $isBuyNow ? 'buy_now' : 'cart' }}">
            <input type="hidden" name="shipping_fee" id="input_shipping_fee" value="0">
            <input type="hidden" name="gift_card_fee" id="input_gift_card_fee" value="0">
            <input type="hidden" name="gift_wrap_fee" id="input_gift_wrap_fee" value="0">

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_23rem] lg:items-start">
                <div class="space-y-5">
                    @if ($isBuyNow && $buyNowProduct)
                        <section class="bloom-panel bloom-panel--padded" x-data="{ quantity: {{ $buyNowQuantity }}, maxQuantity: {{ (int) $buyNowProduct->stock }} }">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-bloom-rose text-xs font-bold text-white"><x-customer.icon name="bag" class="h-4 w-4" /></span>
                                    <div><h2 class="bloom-card-title">Sản phẩm đặt hàng</h2><p class="mt-0.5 text-xs text-bloom-muted">Sản phẩm này tách biệt hoàn toàn khỏi giỏ hàng của bạn.</p></div>
                                </div>
                                <span class="bloom-status bloom-status--info">Đặt hàng</span>
                            </div>
                            <div class="mt-5 flex gap-4 rounded-lg border border-bloom-line p-3 sm:p-4">
                                <x-customer.product-image :product="$buyNowProduct" class="h-20 w-20 shrink-0 rounded-md object-cover sm:h-24 sm:w-24" />
                                <div class="min-w-0 flex-1">
                                    <h3 class="line-clamp-2 text-sm font-semibold text-bloom-ink">{{ $buyNowProduct->name }}</h3>
                                    <div class="mt-1 flex flex-wrap items-baseline gap-2">
                                        <span class="text-sm font-bold text-bloom-rose">{{ number_format($buyNowUnitPrice, 0, ',', '.') }}₫</span>
                                        @if ($buyNowHasSale)
                                            <span class="text-xs text-bloom-muted line-through">{{ number_format($buyNowProduct->price, 0, ',', '.') }}₫</span>
                                        @endif
                                    </div>
                                    <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                                        <div class="bloom-quantity">
                                            <button type="button" @click="quantity = Math.max(1, quantity - 1); $nextTick(() => calculateTotal())" aria-label="Giảm số lượng"><x-customer.icon name="minus" class="h-4 w-4" /></button>
                                            <input id="buy_now_quantity" name="buy_now_quantity" type="number" min="1" :max="maxQuantity" x-model.number="quantity" @input="quantity = Math.min(maxQuantity, Math.max(1, quantity || 1)); calculateTotal()">
                                            <button type="button" @click="quantity = Math.min(maxQuantity, quantity + 1); $nextTick(() => calculateTotal())" aria-label="Tăng số lượng"><x-customer.icon name="plus" class="h-4 w-4" /></button>
                                        </div>
                                        <p class="text-xs text-bloom-muted">Còn {{ $buyNowProduct->stock }} sản phẩm</p>
                                    </div>
                                </div>
                            </div>
                        </section>
                    @endif

                    <section class="bloom-panel bloom-panel--padded">
                        <div class="flex items-center gap-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-bloom-blush text-sm font-bold text-bloom-rose">1</span>
                            <div><h2 class="bloom-card-title">Thông tin người nhận</h2><p class="mt-0.5 text-xs text-bloom-muted">Thông tin dùng cho việc giao đơn hàng.</p></div>
                        </div>
                        <div class="mt-6 grid gap-5 sm:grid-cols-2">
                            <div>
                                <label class="bloom-label" for="recipient_name">Họ và tên người nhận <span class="text-bloom-danger">*</span></label>
                                <input id="recipient_name" type="text" name="recipient_name" class="bloom-input h-11 w-full px-3" required value="{{ old('recipient_name', auth()->user()->name ?? '') }}" placeholder="Ví dụ: Nguyễn Thị Lan">
                                @error('recipient_name') <p class="bloom-field-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="bloom-label" for="recipient_phone">Số điện thoại liên hệ <span class="text-bloom-danger">*</span></label>
                                <input id="recipient_phone" type="text" name="recipient_phone" class="bloom-input h-11 w-full px-3" required value="{{ old('recipient_phone') }}" placeholder="0988xxxxxx">
                                @error('recipient_phone') <p class="bloom-field-error">{{ $message }}</p> @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label class="bloom-label" for="recipient_address">Địa chỉ nhận hoa chi tiết <span class="text-bloom-danger">*</span></label>
                                <input id="recipient_address" type="text" name="recipient_address" class="bloom-input h-11 w-full px-3" required value="{{ old('recipient_address') }}" placeholder="Số nhà, ngõ, tên đường, quận/huyện...">
                                @error('recipient_address') <p class="bloom-field-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </section>

                    <section class="bloom-panel bloom-panel--padded">
                        <div class="flex items-center gap-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-bloom-blush text-sm font-bold text-bloom-rose">2</span>
                            <div><h2 class="bloom-card-title">Lịch giao nhận</h2><p class="mt-0.5 text-xs text-bloom-muted">Chọn ngày và khung giờ còn hoạt động.</p></div>
                        </div>
                        <div class="mt-6 grid gap-5 sm:grid-cols-2">
                            <div>
                                <label class="bloom-label" for="delivery_date">Ngày giao hoa <span class="text-bloom-danger">*</span></label>
                                <input id="delivery_date" type="date" name="delivery_date" class="bloom-input h-11 w-full px-3" required min="{{ date('Y-m-d') }}" value="{{ old('delivery_date', date('Y-m-d')) }}">
                                @error('delivery_date') <p class="bloom-field-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="bloom-label" for="delivery_slot_id">Khung giờ giao hoa <span class="text-bloom-danger">*</span></label>
                                <select id="delivery_slot_id" name="delivery_slot_id" class="bloom-select w-full" required>
                                    <option value="">Chọn khung giờ nhận hoa</option>
                                    @foreach ($deliverySlots as $slot)
                                        <option value="{{ $slot->id }}" @selected((string) old('delivery_slot_id') === (string) $slot->id)>{{ $slot->name }}</option>
                                    @endforeach
                                </select>
                                @error('delivery_slot_id') <p class="bloom-field-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </section>

                    <section class="bloom-panel bloom-panel--padded">
                        <div class="flex items-center gap-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-bloom-blush text-sm font-bold text-bloom-rose">3</span>
                            <div><h2 class="bloom-card-title">Thiệp và gói quà</h2><p class="mt-0.5 text-xs text-bloom-muted">Các tùy chọn này được cộng vào tổng tiền ở bên phải.</p></div>
                        </div>

                        <fieldset class="mt-6">
                            <legend class="bloom-label">Thiệp chúc mừng</legend>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-bloom-line p-3.5 transition hover:border-bloom-rose">
                                    <input class="option-calc mt-0.5 h-4 w-4 border-bloom-line text-bloom-rose focus:ring-bloom-rose" type="radio" name="card_option" id="card_none" value="0" checked data-type="card">
                                    <span><span class="block text-sm font-semibold text-bloom-ink">Không kèm thiệp</span><span class="mt-0.5 block text-xs text-bloom-muted">0₫</span></span>
                                </label>
                                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-bloom-line p-3.5 transition hover:border-bloom-rose">
                                    <input class="option-calc mt-0.5 h-4 w-4 border-bloom-line text-bloom-rose focus:ring-bloom-rose" type="radio" name="card_option" id="card_yes" value="10000" data-type="card">
                                    <span><span class="block text-sm font-semibold text-bloom-ink">Thiệp lời chúc</span><span class="mt-0.5 block text-xs text-bloom-rose">+10.000₫</span></span>
                                </label>
                            </div>
                            <div class="mt-4" id="box_card_message">
                                <label class="bloom-label" for="card_message">Nội dung lời chúc in lên thiệp</label>
                                <textarea id="card_message" name="card_message" class="bloom-textarea w-full" rows="2" placeholder="Ví dụ: Chúc mừng sinh nhật em yêu...">{{ old('card_message') }}</textarea>
                                @error('card_message') <p class="bloom-field-error">{{ $message }}</p> @enderror
                            </div>
                        </fieldset>

                        <fieldset class="mt-6 border-t border-bloom-line pt-5">
                            <legend class="bloom-label">Phong cách gói hoa & quà tặng</legend>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-bloom-line p-3.5 transition hover:border-bloom-rose">
                                    <input class="option-calc mt-0.5 h-4 w-4 border-bloom-line text-bloom-rose focus:ring-bloom-rose" type="radio" name="wrap_option" id="wrap_standard" value="0" checked data-type="wrap">
                                    <span><span class="block text-sm font-semibold text-bloom-ink">Gói tiêu chuẩn</span><span class="mt-0.5 block text-xs text-bloom-muted">0₫</span></span>
                                </label>
                                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-bloom-line p-3.5 transition hover:border-bloom-rose">
                                    <input class="option-calc mt-0.5 h-4 w-4 border-bloom-line text-bloom-rose focus:ring-bloom-rose" type="radio" name="wrap_option" id="wrap_premium" value="30000" data-type="wrap">
                                    <span><span class="block text-sm font-semibold text-bloom-ink">Gói quà cao cấp</span><span class="mt-0.5 block text-xs text-bloom-rose">+30.000₫</span></span>
                                </label>
                            </div>
                        </fieldset>
                    </section>

                    <section class="bloom-panel bloom-panel--padded">
                        <div class="flex items-center gap-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-bloom-blush text-sm font-bold text-bloom-rose">4</span>
                            <div><h2 class="bloom-card-title">Hình thức giao hàng</h2><p class="mt-0.5 text-xs text-bloom-muted">Phí giao hàng sẽ được cộng vào tổng đơn.</p></div>
                        </div>
                        <fieldset class="mt-6 grid gap-3">
                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-bloom-line p-3.5 transition hover:border-bloom-rose">
                                <input class="option-calc mt-0.5 h-4 w-4 border-bloom-line text-bloom-rose focus:ring-bloom-rose" type="radio" name="ship_option" id="ship_standard" value="0" checked data-type="ship">
                                <span><span class="block text-sm font-semibold text-bloom-ink">Giao hàng tiêu chuẩn</span><span class="mt-0.5 block text-xs text-bloom-muted">0₫</span></span>
                            </label>
                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-bloom-line p-3.5 transition hover:border-bloom-rose">
                                <input class="option-calc mt-0.5 h-4 w-4 border-bloom-line text-bloom-rose focus:ring-bloom-rose" type="radio" name="ship_option" id="ship_express" value="30000" data-type="ship">
                                <span><span class="block text-sm font-semibold text-bloom-ink">Giao hàng nhanh</span><span class="mt-0.5 block text-xs text-bloom-rose">+30.000₫</span></span>
                            </label>
                        </fieldset>
                    </section>

                    <section class="bloom-panel bloom-panel--padded">
                        <div class="flex items-center gap-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-bloom-blush text-sm font-bold text-bloom-rose">5</span>
                            <div><h2 class="bloom-card-title">Ghi chú và thanh toán</h2><p class="mt-0.5 text-xs text-bloom-muted">Chọn một phương thức thanh toán để tiếp tục.</p></div>
                        </div>
                        <div class="mt-6">
                            <label class="bloom-label" for="order_note">Ghi chú cho đơn hàng</label>
                            <textarea id="order_note" name="order_note" class="bloom-textarea w-full" rows="3" placeholder="Ghi chú thêm cho đơn hàng (nếu có)">{{ old('order_note') }}</textarea>
                            @error('order_note') <p class="bloom-field-error">{{ $message }}</p> @enderror
                        </div>
                        <fieldset class="mt-6 grid gap-3 sm:grid-cols-2">
                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-bloom-line p-4 transition hover:border-bloom-rose">
                                <input class="mt-0.5 h-4 w-4 border-bloom-line text-bloom-rose focus:ring-bloom-rose" type="radio" name="payment_method" id="pay_cod" value="cod" @checked(old('payment_method', 'cod') === 'cod')>
                                <span><span class="flex items-center gap-2 text-sm font-semibold text-bloom-ink"><x-customer.icon name="bag" class="h-4 w-4 text-bloom-rose" />Thanh toán khi nhận hoa</span><span class="mt-1 block text-xs text-bloom-muted">COD</span></span>
                            </label>
                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-bloom-line p-4 transition hover:border-bloom-rose">
                                <input class="mt-0.5 h-4 w-4 border-bloom-line text-bloom-rose focus:ring-bloom-rose" type="radio" name="payment_method" id="pay_paypal" value="paypal" @checked(old('payment_method') === 'paypal')>
                                <span><span class="flex items-center gap-2 text-sm font-semibold text-bloom-ink"><x-customer.icon name="credit-card" class="h-4 w-4 text-bloom-rose" />PayPal</span><span class="mt-1 block text-xs text-bloom-muted">Thanh toán trực tuyến qua PayPal</span></span>
                            </label>
                        </fieldset>
                        @error('payment_method') <p class="bloom-field-error">{{ $message }}</p> @enderror
                    </section>
                </div>

                <aside class="space-y-5 lg:sticky lg:top-28">
                    <section class="bloom-panel bloom-panel--padded">
                        <div class="flex items-center gap-2"><x-customer.icon name="gift" class="h-5 w-5 text-bloom-rose" /><h2 class="font-display text-lg font-semibold text-bloom-plum">Ưu đãi & giảm giá</h2></div>
                        @if ($activeVouchers->isNotEmpty())
                            <div class="mt-4 space-y-2">
                                @foreach ($activeVouchers as $voc)
                                    <div class="flex items-center justify-between gap-3 rounded-lg bg-bloom-blush p-3">
                                        <div class="min-w-0"><p class="text-xs font-bold tracking-[.08em] text-bloom-rose">{{ $voc->code }}</p><p class="mt-0.5 truncate text-xs text-bloom-muted">{{ $voc->name }}</p></div>
                                        <button type="button" onclick="applyVoucherCode('{{ $voc->code }}')" class="bloom-button bloom-button--ghost bloom-button--small shrink-0">Chọn</button>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        <div class="mt-4 flex gap-2">
                            <label class="sr-only" for="voucher_input_code">Mã giảm giá</label>
                            <input type="text" id="voucher_input_code" class="bloom-input h-10 min-w-0 flex-1 px-3 text-sm uppercase" placeholder="NHẬP MÃ...">
                            <button class="bloom-button bloom-button--small" type="button" onclick="submitVoucher()">Áp dụng</button>
                        </div>
                        @if ($voucherCode)
                            <p class="mt-3 text-xs font-medium text-emerald-700">Đang áp dụng mã {{ $voucherCode }}.</p>
                        @endif
                    </section>

                    <section class="bloom-panel bloom-panel--padded">
                        <div class="flex items-center justify-between gap-3"><h2 class="font-display text-lg font-semibold text-bloom-plum">Tóm tắt đơn hàng</h2><span id="label_item_count" class="text-xs text-bloom-muted">{{ $cartItems->sum('quantity') }} sản phẩm</span></div>
                        <div class="mt-4 max-h-52 space-y-3 overflow-y-auto border-b border-bloom-line pb-4">
                            @foreach ($cartItems as $item)
                                <div class="flex justify-between gap-3 text-sm"><span class="min-w-0 text-bloom-muted">{{ $item->product->name ?? 'Hoa tươi' }} <span @if($isBuyNow) id="label_buy_now_quantity" @endif class="whitespace-nowrap">× {{ $item->quantity }}</span></span><span @if($isBuyNow) id="label_buy_now_line_total" @endif class="whitespace-nowrap font-semibold text-bloom-ink">{{ number_format(($item->price ?? $item->product->price) * $item->quantity, 0, ',', '.') }}₫</span></div>
                            @endforeach
                        </div>
                        <div class="mt-4 space-y-2.5 text-sm">
                            <div class="flex justify-between gap-3"><span class="text-bloom-muted">Tạm tính</span><span id="label_subtotal" class="font-semibold text-bloom-ink">{{ number_format($subtotal, 0, ',', '.') }}₫</span></div>
                            <div class="flex justify-between gap-3"><span class="text-bloom-muted">Phí giao hàng</span><span id="label_shipping_fee" class="font-semibold text-bloom-ink">0₫</span></div>
                            <div class="flex justify-between gap-3"><span class="text-bloom-muted">Thiệp chúc</span><span id="label_gift_card_fee" class="font-semibold text-bloom-ink">0₫</span></div>
                            <div class="flex justify-between gap-3"><span class="text-bloom-muted">Gói quà</span><span id="label_gift_wrap_fee" class="font-semibold text-bloom-ink">0₫</span></div>
                            <div class="flex justify-between gap-3"><span class="text-bloom-muted">Giảm giá voucher</span><span class="font-semibold text-emerald-700">-{{ number_format($voucherDiscount, 0, ',', '.') }}₫</span></div>
                        </div>
                        <div class="mt-5 flex items-end justify-between gap-3 border-t border-bloom-line pt-4"><span class="font-semibold text-bloom-ink">Tổng thanh toán</span><span id="label_total_amount" class="text-xl font-bold text-bloom-rose">{{ number_format(max(0, $subtotal - $voucherDiscount), 0, ',', '.') }}₫</span></div>
                        <button type="submit" class="bloom-button mt-5 w-full">{{ $isBuyNow ? 'Đặt mua' : 'Đặt hàng ngay' }} <x-customer.icon name="shield" class="h-4 w-4" /></button>
                        <p class="mt-3 text-center text-xs leading-5 text-bloom-muted">Khi đặt hàng, bạn đồng ý với các điều khoản giao dịch hiện có của BloomGift.</p>
                    </section>
                </aside>
            </div>
        </form>
    </section>

    <script>
        const initialSubtotal = {{ (float) $subtotal }};
        const discount = {{ (float) $voucherDiscount }};
        const isBuyNow = {{ $isBuyNow ? 'true' : 'false' }};
        const buyNowUnitPrice = {{ $buyNowUnitPrice }};

        function formatNumber(num) {
            return new Intl.NumberFormat('vi-VN').format(num) + '₫';
        }

        function getSubtotal() {
            if (!isBuyNow) {
                return initialSubtotal;
            }

            const quantity = Math.max(1, parseInt(document.getElementById('buy_now_quantity')?.value || '1', 10));
            return buyNowUnitPrice * quantity;
        }

        function calculateTotal() {
            const shipFee = parseFloat(document.querySelector('input[name="ship_option"]:checked').value);
            const cardFee = parseFloat(document.querySelector('input[name="card_option"]:checked').value);
            const wrapFee = parseFloat(document.querySelector('input[name="wrap_option"]:checked').value);

            document.getElementById('input_shipping_fee').value = shipFee;
            document.getElementById('input_gift_card_fee').value = cardFee;
            document.getElementById('input_gift_wrap_fee').value = wrapFee;

            document.getElementById('label_shipping_fee').innerText = formatNumber(shipFee);
            document.getElementById('label_gift_card_fee').innerText = formatNumber(cardFee);
            document.getElementById('label_gift_wrap_fee').innerText = formatNumber(wrapFee);

            const subtotal = getSubtotal();
            const finalTotal = Math.max(0, subtotal + shipFee + cardFee + wrapFee - discount);

            document.getElementById('label_subtotal').innerText = formatNumber(subtotal);
            document.getElementById('label_total_amount').innerText = formatNumber(finalTotal);

            if (isBuyNow) {
                const quantity = Math.max(1, parseInt(document.getElementById('buy_now_quantity').value || '1', 10));
                document.getElementById('label_item_count').innerText = quantity + ' sản phẩm';
                document.getElementById('label_buy_now_quantity').innerText = '× ' + quantity;
                document.getElementById('label_buy_now_line_total').innerText = formatNumber(subtotal);
            }
        }

        document.querySelectorAll('.option-calc').forEach(radio => radio.addEventListener('change', calculateTotal));

        function applyVoucherCode(code) {
            document.getElementById('voucher_input_code').value = code;
        }

        function submitVoucher() {
            const code = document.getElementById('voucher_input_code').value.trim();
            if (!code) {
                alert('Vui lòng nhập mã giảm giá!');
                return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("voucher.apply") }}';

            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = '{{ csrf_token() }}';
            form.appendChild(csrfInput);

            const codeInput = document.createElement('input');
            codeInput.type = 'hidden';
            codeInput.name = 'code';
            codeInput.value = code;
            form.appendChild(codeInput);

            const modeInput = document.createElement('input');
            modeInput.type = 'hidden';
            modeInput.name = 'checkout_mode';
            modeInput.value = isBuyNow ? 'buy_now' : 'cart';
            form.appendChild(modeInput);

            if (isBuyNow) {
                const quantityInput = document.createElement('input');
                quantityInput.type = 'hidden';
                quantityInput.name = 'buy_now_quantity';
                quantityInput.value = document.getElementById('buy_now_quantity').value;
                form.appendChild(quantityInput);
            }

            document.body.appendChild(form);
            form.submit();
        }

        calculateTotal();
    </script>
</x-customer.layout>
