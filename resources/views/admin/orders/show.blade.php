<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Chi tiết đơn hàng #{{ $order->order_code ?? $order->id }} - BloomGift</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #fff1f6;
            color: #333;
        }

        .container {
            width: 92%;
            max-width: 1050px;
            margin: 40px auto;
        }

        h1 {
            color: #d92d6b;
            margin-bottom: 25px;
        }

        h2 {
            margin-top: 0;
            color: #b51f56;
        }

        .card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, .08);
        }

        .order-code {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .box {
            background: #fff8fa;
            padding: 16px;
            border-radius: 10px;
            border: 1px solid #f8dce6;
        }

        .box strong {
            display: block;
            color: #b51f56;
            margin-bottom: 7px;
        }

        .status {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 20px;
            background: #ffe0eb;
            color: #d92d6b;
            font-weight: bold;
        }

        .paid {
            color: #16a34a;
            font-weight: bold;
        }

        .unpaid {
            color: #dc2626;
            font-weight: bold;
        }

        .payment-box {
            margin-top: 12px;
            padding: 12px;
            background: #f0fdf4;
            border-radius: 8px;
            border: 1px solid #bbf7d0;
        }

        .payment-code {
            margin-top: 5px;
            font-size: 13px;
            color: #555;
            word-break: break-all;
        }

        select {
            padding: 11px 14px;
            border: 1px solid #ddd;
            border-radius: 7px;
            min-width: 250px;
            background: white;
            font-size: 14px;
        }

        button {
            padding: 11px 18px;
            background: #d92d6b;
            color: white;
            border: none;
            border-radius: 7px;
            cursor: pointer;
            font-weight: bold;
            margin-left: 8px;
        }

        button:hover {
            background: #b51f56;
        }

        .product {
            padding: 16px 0;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            gap: 20px;
        }

        .product-name {
            font-weight: bold;
            margin-bottom: 6px;
        }

        .product-info {
            color: #777;
            font-size: 14px;
        }

        .product-subtotal {
            font-weight: bold;
            color: #d92d6b;
            white-space: nowrap;
        }

        .summary {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 7px 0;
        }

        .summary-row.total {
            margin-top: 10px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            font-size: 21px;
            font-weight: bold;
            color: #d92d6b;
        }

        .note {
            background: #fff8fa;
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
            border-left: 4px solid #d92d6b;
        }

        .btn-back {
            display: inline-block;
            background: #d92d6b;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
        }

        .btn-back:hover {
            background: #b51f56;
        }

        .alert-success {
            background: #d1fae5;
            color: #047857;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .alert-error {
            background: #fee2e2;
            color: #b91c1c;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        /* Thanh tiến trình dành riêng cho khách hàng */
        .track-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin: 25px 0 10px;
        }

        .track-steps::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 20px;
            right: 20px;
            height: 3px;
            background: #fcd5e5;
            z-index: 1;
        }

        .step {
            position: relative;
            z-index: 2;
            text-align: center;
            background: white;
            padding: 0 8px;
        }

        .step-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #f3f4f6;
            color: #9ca3af;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 6px;
        }

        .step.active .step-circle {
            background: #d92d6b;
            color: white;
        }

        .step.active .step-text {
            color: #d92d6b;
            font-weight: bold;
        }

        .step-text {
            font-size: 12px;
            color: #6b7280;
        }

        @media(max-width: 700px) {
            .container {
                width: 94%;
                margin: 20px auto;
            }

            .grid {
                grid-template-columns: 1fr;
            }

            .product {
                flex-direction: column;
            }

            .product-subtotal {
                text-align: left;
            }

            select {
                width: 100%;
                min-width: 0;
            }

            button {
                margin-left: 0;
                margin-top: 10px;
                width: 100%;
            }

            .summary-row.total {
                font-size: 18px;
            }
        }
    </style>
</head>

<body>

    <div class="container">

        <h1>🌸 Chi tiết đơn đặt hoa</h1>

        {{-- THÔNG BÁO --}}
        @if(session('success'))
            <div class="alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert-error">
                {{ session('error') }}
            </div>
        @endif

        {{-- =========================
        THÔNG TIN ĐƠN HÀNG
        ========================= --}}
        <div class="card">
            <div class="order-code">
                {{ $order->order_code ?? ('BG-' . $order->id) }}
            </div>

            <div class="grid">
                {{-- Khách hàng --}}
                <div class="box">
                    <strong>Người nhận hoa</strong>
                    {{ $order->recipient_name }}
                </div>

                {{-- Số điện thoại --}}
                <div class="box">
                    <strong>Số điện thoại</strong>
                    {{ $order->recipient_phone }}
                </div>

                {{-- Địa chỉ --}}
                <div class="box">
                    <strong>Địa chỉ giao hàng</strong>
                    {{ $order->recipient_address }}
                </div>

                {{-- Ngày giao --}}
                <div class="box">
                    <strong>Ngày giao</strong>
                    {{ $order->delivery_date ? \Carbon\Carbon::parse($order->delivery_date)->format('d/m/Y') : 'Chưa chọn' }}
                </div>

                {{-- Khung giờ --}}
                <div class="box">
                    <strong>Khung giờ giao</strong>
                    @if($order->deliverySlot)
                        {{ $order->deliverySlot->name ?? $order->deliverySlot->time_range }}
                    @else
                        Tiêu chuẩn
                    @endif
                </div>

                {{-- Trạng thái đơn --}}
                <div class="box">
                    <strong>Trạng thái đơn hàng</strong>
                    @php
                        $curStatus = strtolower($order->status ?? $order->order_status ?? 'pending');
                        $statusLabels = [
                            'pending' => 'Chờ xác nhận',
                            'confirmed' => 'Đã xác nhận',
                            'preparing' => 'Đang cắm hoa',
                            'processing' => 'Đang xử lý',
                            'shipping' => 'Đang giao hoa',
                            'completed' => 'Hoàn thành',
                            'cancelled' => 'Đã hủy',
                        ];
                    @endphp
                    <span class="status">
                        {{ $statusLabels[$curStatus] ?? strtoupper($curStatus) }}
                    </span>
                </div>

                {{-- Phương thức thanh toán --}}
                <div class="box">
                    <strong>Phương thức thanh toán</strong>
                    @if($order->payment_method === 'paypal')
                        PayPal Sandbox
                    @elseif($order->payment_method === 'cod')
                        Thanh toán khi nhận hàng (COD)
                    @else
                        {{ strtoupper($order->payment_method) }}
                    @endif
                </div>

                {{-- Trạng thái thanh toán --}}
                <div class="box">
                    <strong>Trạng thái thanh toán</strong>
                    @if($order->payment_status === 'paid')
                        <span class="paid">✓ Đã thanh toán</span>
                    @else
                        <span class="unpaid">Chưa thanh toán</span>
                    @endif
                </div>
            </div>

            {{-- Thông tin PayPal --}}
            @if($order->payment && $order->payment_method === 'paypal')
                <div class="payment-box">
                    <strong>Thông tin giao dịch PayPal</strong>
                    @if($order->payment->transaction_id)
                        <div class="payment-code">
                            Mã giao dịch: {{ $order->payment->transaction_id }}
                        </div>
                    @endif
                    <div class="payment-code">
                        Trạng thái: {{ strtoupper($order->payment->status) }}
                    </div>
                </div>
            @endif

            {{-- Ghi chú & Lời chúc thiệp --}}
            @if($order->note)
                <div class="note">
                    <strong>💌 Lời chúc thiệp & Ghi chú</strong>
                    <p style="margin: 6px 0 0;">{{ $order->note }}</p>
                </div>
            @endif
        </div>

        {{-- ========================================================
        CẬP NHẬT TRẠNG THÁI (CHỈ HIỂN THỊ KHI ĐĂNG NHẬP ADMIN)
        ======================================================== --}}
        @if(Auth::check() && Auth::user()->role === 'admin')
            <div class="card">
                <h2>Cập nhật trạng thái đơn hàng (Dành cho Quản trị viên)</h2>
                <form method="POST" action="{{ route('admin.orders.update-status', $order) }}">
                    @csrf
                    @method('PATCH')

                    <select name="order_status">
                        <option value="pending" {{ in_array($curStatus, ['pending']) ? 'selected' : '' }}>Chờ xác nhận
                        </option>
                        <option value="confirmed" {{ in_array($curStatus, ['confirmed']) ? 'selected' : '' }}>Đã xác nhận
                        </option>
                        <option value="preparing" {{ in_array($curStatus, ['preparing', 'processing']) ? 'selected' : '' }}>
                            Đang cắm hoa</option>
                        <option value="shipping" {{ in_array($curStatus, ['shipping']) ? 'selected' : '' }}>Đang giao
                        </option>
                        <option value="completed" {{ in_array($curStatus, ['completed']) ? 'selected' : '' }}>Hoàn thành
                        </option>
                        <option value="cancelled" {{ in_array($curStatus, ['cancelled']) ? 'selected' : '' }}>Đã hủy
                        </option>
                    </select>

                    <button type="submit">Cập nhật trạng thái</button>
                </form>
            </div>
        @else
            {{-- NẾU LÀ KHÁCH HÀNG: HIỂN THỊ TIẾN TRÌNH THEO DÕI ĐƠN HÀNG THAY VÌ FORM SỬA --}}
            @php
                $steps = [
                    'pending' => 1,
                    'confirmed' => 2,
                    'preparing' => 3,
                    'processing' => 3,
                    'shipping' => 4,
                    'completed' => 5,
                ];
                $currentStep = $steps[$curStatus] ?? 1;
            @endphp
            <div class="card">
                <h2>Tiến trình chuẩn bị đơn hoa của bạn</h2>
                <div class="track-steps">
                    <div class="step {{ $currentStep >= 1 ? 'active' : '' }}">
                        <div class="step-circle">1</div>
                        <div class="step-text">Tiếp nhận</div>
                    </div>
                    <div class="step {{ $currentStep >= 2 ? 'active' : '' }}">
                        <div class="step-circle">2</div>
                        <div class="step-text">Đã duyệt</div>
                    </div>
                    <div class="step {{ $currentStep >= 3 ? 'active' : '' }}">
                        <div class="step-circle">3</div>
                        <div class="step-text">Cắm hoa & viết thiệp</div>
                    </div>
                    <div class="step {{ $currentStep >= 4 ? 'active' : '' }}">
                        <div class="step-circle">4</div>
                        <div class="step-text">Đang giao</div>
                    </div>
                    <div class="step {{ $currentStep >= 5 ? 'active' : '' }}">
                        <div class="step-circle">5</div>
                        <div class="step-text">Đã trao hoa</div>
                    </div>
                </div>
            </div>
        @endif

        {{-- =========================
        DANH SÁCH SẢN PHẨM
        ========================= --}}
        <div class="card">
            <h2>Sản phẩm trong đơn</h2>
            @php
                $orderItemsList = ($order->orderItems && $order->orderItems->isNotEmpty()) ? $order->orderItems : ($order->items ?? collect());
            @endphp

            @forelse($orderItemsList as $item)
                <div class="product">
                    <div>
                        <div class="product-name">
                            🌸 {{ $item->product_name ?? ($item->product->name ?? 'Bó hoa tươi') }}
                        </div>
                        <div class="product-info">
                            Đơn giá: {{ number_format($item->price, 0, ',', '.') }}đ × {{ $item->quantity }}
                        </div>
                    </div>
                    <div class="product-subtotal">
                        {{ number_format($item->subtotal ?? ($item->price * $item->quantity), 0, ',', '.') }}đ
                    </div>
                </div>
            @empty
                <p>Không có sản phẩm trong đơn hàng.</p>
            @endforelse

            {{-- =========================
            TỔNG TIỀN
            ========================= --}}
            <div class="summary">
                <div class="summary-row">
                    <span>Tạm tính tiền hoa</span>
                    <span>{{ number_format($order->subtotal, 0, ',', '.') }}đ</span>
                </div>

                @if($order->discount > 0)
                    <div class="summary-row">
                        <span>Ưu đãi giảm giá (Voucher)</span>
                        <span style="color:#16a34a;">-{{ number_format($order->discount, 0, ',', '.') }}đ</span>
                    </div>
                @endif

                <div class="summary-row">
                    <span>Phí giao hàng & Dịch vụ</span>
                    <span>{{ number_format($order->shipping_fee, 0, ',', '.') }}đ</span>
                </div>

                <div class="summary-row total">
                    <span>Tổng thanh toán</span>
                    <span>{{ number_format($order->total ?? $order->total_amount, 0, ',', '.') }}đ</span>
                </div>
            </div>
        </div>

        {{-- QUAY LẠI --}}
        @if(Auth::check() && Auth::user()->role === 'admin')
            <a href="{{ route('admin.orders.index') }}" class="btn-back">
                ← Quay lại danh sách quản trị
            </a>
        @else
            <a href="{{ route('customer.orders') }}" class="btn-back">
                ← Quay lại đơn hàng của tôi
            </a>
        @endif

    </div>

</body>

</html>