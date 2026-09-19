<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán đơn hoa - BloomGift</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-rose: #e11d48;
            --soft-rose: #fff0f3;
            --border-rose: #ffe4e6;
        }

        body {
            background-color: #fff5f7;
            font-family: system-ui, -apple-system, sans-serif;
            color: #374151;
        }

        .checkout-box {
            background: #fff;
            border-radius: 16px;
            border: 1px solid var(--border-rose);
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(225, 29, 72, 0.03);
        }

        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: #881337;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-control,
        .form-select {
            border-color: #fecdd3;
            border-radius: 10px;
            font-size: 14px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-rose);
            box-shadow: 0 0 0 0.2rem rgba(225, 29, 72, 0.15);
        }

        .service-card {
            border: 1px solid #fed7aa;
            border-radius: 12px;
            padding: 12px;
            cursor: pointer;
            transition: all 0.2s;
            background: #fffaf0;
        }

        .service-card:hover {
            border-color: var(--primary-rose);
        }

        .btn-order {
            background-color: var(--primary-rose);
            color: #fff;
            font-weight: 700;
            border-radius: 9999px;
            padding: 14px;
            border: none;
            width: 100%;
            transition: opacity 0.2s;
        }

        .btn-order:hover {
            opacity: 0.95;
            color: #fff;
        }
    </style>
</head>

<body>

    <div class="container py-4">
        <!-- Header -->
        <div class="text-center mb-4">
            <h2 class="h3 fw-bold text-dark">🌸 BloomGift - Thanh toán đơn hoa</h2>
            <p class="text-muted small">Trao gửi yêu thương - Cam kết hoa tươi trong ngày</p>
        </div>

        @if(session('error'))
            <div class="alert alert-danger rounded-3 py-2 small mb-3">⚠️ {{ session('error') }}</div>
        @endif

        <form action="{{ route('checkout.process') }}" method="POST" id="checkout-form">
            @csrf

            <!-- Các biến tính toán ẩn để gửi lên server -->
            <input type="hidden" name="shipping_fee" id="input_shipping_fee" value="0">
            <input type="hidden" name="gift_card_fee" id="input_gift_card_fee" value="0">
            <input type="hidden" name="gift_wrap_fee" id="input_gift_wrap_fee" value="0">

            <div class="row g-4">
                <!-- Cột trái: Form thông tin & Dịch vụ đi kèm -->
                <div class="col-lg-7">

                    <!-- 1. Thông tin người nhận -->
                    <div class="checkout-box">
                        <div class="section-title">1. Thông tin người nhận hoa</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Họ và tên người nhận <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="recipient_name" class="form-control" required
                                    value="{{ old('recipient_name', auth()->user()->name ?? '') }}"
                                    placeholder="Ví dụ: Nguyễn Thị Lan">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Số điện thoại liên hệ <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="recipient_phone" class="form-control" required
                                    value="{{ old('recipient_phone') }}" placeholder="0988xxxxxx">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Địa chỉ nhận hoa chi tiết <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="recipient_address" class="form-control" required
                                    value="{{ old('recipient_address') }}"
                                    placeholder="Số nhà, ngõ, tên đường, quận/huyện...">
                            </div>
                        </div>
                    </div>

                    <!-- 2. Thời gian giao hoa -->
                    <div class="checkout-box">
                        <div class="section-title">2. Lịch trình giao nhận hoa</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Ngày giao hoa <span
                                        class="text-danger">*</span></label>
                                <input type="date" name="delivery_date" class="form-control" required
                                    min="{{ date('Y-m-d') }}" value="{{ old('delivery_date', date('Y-m-d')) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Khung giờ giao hoa <span
                                        class="text-danger">*</span></label>
                                <select name="delivery_slot_id" class="form-select" required>
                                    <option value="">-- Chọn khung giờ nhận hoa --</option>
                                    @foreach($deliverySlots as $slot)
                                        <option value="{{ $slot->id }}">{{ $slot->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Tùy chọn Thiệp chúc & Gói quà cao cấp -->
                    <div class="checkout-box">
                        <div class="section-title">3. Tùy chọn Thiệp chúc mừng & Gói quà</div>

                        <!-- Chọn thiệp -->
                        <label class="form-label small fw-bold text-secondary mb-2">💌 Dịch vụ thiệp chúc mừng:</label>
                        <div class="d-flex gap-3 mb-3">
                            <div class="form-check">
                                <input class="form-check-input option-calc" type="radio" name="card_option"
                                    id="card_none" value="0" checked data-type="card">
                                <label class="form-check-label small" for="card_none">Không kèm thiệp (0đ)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input option-calc" type="radio" name="card_option"
                                    id="card_yes" value="10000" data-type="card">
                                <label class="form-check-label small" for="card_yes">Thiệp hoa thiết kế cao cấp
                                    (+10.000đ)</label>
                            </div>
                        </div>

                        <!-- Ô nhập lời chúc thiệp -->
                        <div class="mb-4" id="box_card_message">
                            <label class="form-label small fw-bold">Nội dung lời chúc in lên thiệp:</label>
                            <textarea name="card_message" class="form-control" rows="2"
                                placeholder="Ví dụ: Chúc mừng sinh nhật em yêu, mãi luôn rạng rỡ như đóa hoa này nhé!"></textarea>
                        </div>

                        <hr class="border-light-subtle my-3">

                        <!-- Chọn gói quà -->
                        <label class="form-label small fw-bold text-secondary mb-2">🎁 Phong cách gói hoa & Quà
                            tặng:</label>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="service-card d-flex align-items-center gap-2">
                                    <input class="form-check-input mt-0 option-calc" type="radio" name="wrap_option"
                                        id="wrap_standard" value="0" checked data-type="wrap">
                                    <label class="small cursor-pointer mb-0" for="wrap_standard">
                                        <strong>Gói giấy lụa tiêu chuẩn</strong><br>
                                        <span class="text-muted text-xs">Mặc định của tiệm hoa (0đ)</span>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="service-card d-flex align-items-center gap-2">
                                    <input class="form-check-input mt-0 option-calc" type="radio" name="wrap_option"
                                        id="wrap_premium" value="30000" data-type="wrap">
                                    <label class="small cursor-pointer mb-0" for="wrap_premium">
                                        <strong>Hộp quà nắp kính BloomGift</strong><br>
                                        <span class="text-danger fw-bold">+30.000đ</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Phương thức vận chuyển -->
                    <div class="checkout-box">
                        <div class="section-title">4. Hình thức giao hàng</div>
                        <div class="d-flex flex-column gap-2">
                            <div class="form-check">
                                <input class="form-check-input option-calc" type="radio" name="ship_option"
                                    id="ship_standard" value="0" checked data-type="ship">
                                <label class="form-check-label small" for="ship_standard">
                                    🚚 <strong>Giao hàng tiêu chuẩn:</strong> Miễn phí trong bán kính 5km (0đ)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input option-calc" type="radio" name="ship_option"
                                    id="ship_express" value="30000" data-type="ship">
                                <label class="form-check-label small" for="ship_express">
                                    ⚡ <strong>Giao hoa hỏa tốc đúng giờ hẹn:</strong> Bảo quản thùng lạnh (+30.000đ)
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- 5. Phương thức thanh toán -->
                    <div class="checkout-box">
                        <div class="section-title">5. Phương thức thanh toán</div>
                        <div class="d-flex flex-column gap-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="pay_cod"
                                    value="cod" checked>
                                <label class="form-check-label small fw-bold" for="pay_cod">
                                    💵 Thanh toán khi nhận hoa (COD)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="pay_paypal"
                                    value="paypal">
                                <label class="form-check-label small fw-bold" for="pay_paypal">
                                    💳 Thanh toán trực tuyến qua PayPal Sandbox
                                </label>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Cột phải: Voucher & Tóm tắt đơn thực tế -->
                <div class="col-lg-5">

                    <!-- Mã giảm giá Voucher -->
                    <div class="checkout-box">
                        <div class="section-title">🎟️ Ưu đãi & Giảm giá</div>
                        @if($activeVouchers->isNotEmpty())
                            <div class="mb-3">
                                <small class="text-danger fw-bold">⚡ Flash Sale đang diễn ra:</small>
                                <div class="d-flex flex-column gap-2 mt-1">
                                    @foreach($activeVouchers as $voc)
                                        <div
                                            class="p-2 border rounded-3 bg-light d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="badge bg-danger">{{ $voc->code }}</span>
                                                <small class="d-block text-muted"
                                                    style="font-size: 11px;">{{ $voc->name }}</small>
                                            </div>
                                            <button type="button" onclick="applyVoucherCode('{{ $voc->code }}')"
                                                class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size: 12px;">Áp
                                                dụng</button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="input-group">
                            <input type="text" id="voucher_input_code"
                                class="form-control text-uppercase font-monospace" placeholder="NHẬP MÃ...">
                            <button class="btn btn-danger" type="button" onclick="submitVoucher()">Áp dụng</button>
                        </div>
                    </div>

                    <!-- Tóm tắt đơn hoa động từ DB -->
                    <div class="checkout-box">
                        <div class="section-title">📋 Tóm tắt đơn hàng ({{ $cartItems->sum('quantity') }} sản phẩm)
                        </div>

                        <!-- Danh sách hoa từ Giỏ hàng -->
                        <div class="d-flex flex-column gap-2 mb-3 pb-3 border-bottom">
                            @foreach($cartItems as $item)
                                <div class="d-flex justify-content-between small">
                                    <span>{{ $item->product->name ?? 'Hoa tươi' }} &times; {{ $item->quantity }}</span>
                                    <span
                                        class="fw-bold">{{ number_format(($item->price ?? $item->product->price) * $item->quantity, 0, ',', '.') }}
                                        đ</span>
                                </div>
                            @endforeach
                        </div>

                        <!-- Bảng tính tiền chi tiết -->
                        <div class="d-flex justify-content-between small text-secondary mb-2">
                            <span>Tạm tính hoa tươi:</span>
                            <span class="text-dark fw-bold">{{ number_format($subtotal, 0, ',', '.') }} đ</span>
                        </div>

                        <div class="d-flex justify-content-between small text-secondary mb-2">
                            <span>Phí giao hàng:</span>
                            <span id="label_shipping_fee" class="fw-semibold text-dark">0 đ</span>
                        </div>

                        <div class="d-flex justify-content-between small text-secondary mb-2">
                            <span>Thiệp chúc:</span>
                            <span id="label_gift_card_fee" class="fw-semibold text-dark">0 đ</span>
                        </div>

                        <div class="d-flex justify-content-between small text-secondary mb-2">
                            <span>Gói quà cao cấp:</span>
                            <span id="label_gift_wrap_fee" class="fw-semibold text-dark">0 đ</span>
                        </div>

                        <div class="d-flex justify-content-between small text-secondary mb-3">
                            <span>Giảm giá Voucher:</span>
                            <span class="text-danger fw-bold">-{{ number_format($voucherDiscount, 0, ',', '.') }}
                                đ</span>
                        </div>

                        <hr class="border-light-subtle my-3">

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <span class="fw-bold">Tổng thanh toán:</span>
                            <span id="label_total_amount" class="fs-4 fw-bold text-danger">
                                {{ number_format(max(0, $subtotal - $voucherDiscount), 0, ',', '.') }} đ
                            </span>
                        </div>

                        <button type="submit" class="btn-order shadow-sm">
                            🔒 Đặt hàng ngay
                        </button>
                    </div>

                </div>
            </div>
        </form>
    </div>

    <script>
        const subtotal = {{ (float) $subtotal }};
        const discount = {{ (float) $voucherDiscount }};

        function formatNumber(num) {
            return new Intl.NumberFormat('vi-VN').format(num) + ' đ';
        }

        function calculateTotal() {
            const shipFee = parseFloat(document.querySelector('input[name="ship_option"]:checked').value);
            const cardFee = parseFloat(document.querySelector('input[name="card_option"]:checked').value);
            const wrapFee = parseFloat(document.querySelector('input[name="wrap_option"]:checked').value);

            // Đổ giá trị vào hidden inputs
            document.getElementById('input_shipping_fee').value = shipFee;
            document.getElementById('input_gift_card_fee').value = cardFee;
            document.getElementById('input_gift_wrap_fee').value = wrapFee;

            // Cập nhật nhãn hiển thị
            document.getElementById('label_shipping_fee').innerText = formatNumber(shipFee);
            document.getElementById('label_gift_card_fee').innerText = formatNumber(cardFee);
            document.getElementById('label_gift_wrap_fee').innerText = formatNumber(wrapFee);

            // Tính tổng tiền cuối cùng
            const finalTotal = Math.max(0, subtotal + shipFee + cardFee + wrapFee - discount);
            document.getElementById('label_total_amount').innerText = formatNumber(finalTotal);
        }

        // Lắng nghe sự kiện thay đổi radio
        document.querySelectorAll('.option-calc').forEach(radio => {
            radio.addEventListener('change', calculateTotal);
        });

        function applyVoucherCode(code) {
            document.getElementById('voucher_input_code').value = code;
        }

        function submitVoucher() {
            const code = document.getElementById('voucher_input_code').value.trim();
            if (!code) {
                alert('Vui lòng nhập mã giảm giá!');
                return;
            }

            // Tạo form ảo gửi POST áp dụng voucher
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

            document.body.appendChild(form);
            form.submit();
        }

        // Chạy tính toán lần đầu khi nạp trang
        calculateTotal();
    </script>
</body>

</html>