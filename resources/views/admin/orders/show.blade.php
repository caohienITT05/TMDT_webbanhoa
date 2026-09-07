<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Chi tiết đơn hàng - Admin | BloomGift</title>

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

    <h1>🌸 Chi tiết đơn hàng</h1>


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
            {{ $order->order_code }}
        </div>

        <div class="grid">

            {{-- Khách hàng --}}
            <div class="box">
                <strong>Khách hàng</strong>

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

                {{ $order->delivery_date?->format('d/m/Y') ?? 'Chưa chọn' }}
            </div>


            {{-- Khung giờ --}}
            <div class="box">
                <strong>Khung giờ giao</strong>

                @if($order->deliverySlot)

                    {{ $order->deliverySlot->name }}

                    <br>

                    <small>
                        {{ $order->deliverySlot->start_time }}
                        -
                        {{ $order->deliverySlot->end_time }}
                    </small>

                @else

                    Chưa chọn

                @endif

            </div>


            {{-- Trạng thái đơn --}}
            <div class="box">
                <strong>Trạng thái đơn hàng</strong>

                @php
                    $statusLabels = [
                        'pending' => 'Chờ xác nhận',
                        'confirmed' => 'Đã xác nhận',
                        'processing' => 'Đang xử lý',
                        'shipping' => 'Đang giao',
                        'completed' => 'Hoàn thành',
                        'cancelled' => 'Đã hủy',
                    ];
                @endphp

                <span class="status">
                    {{ $statusLabels[$order->order_status] ?? $order->order_status }}
                </span>
            </div>


            {{-- Phương thức thanh toán --}}
            <div class="box">
                <strong>Phương thức thanh toán</strong>

                @if($order->payment_method === 'paypal')

                    PayPal

                @elseif($order->payment_method === 'cod')

                    Thanh toán khi nhận hàng

                @else

                    {{ strtoupper($order->payment_method) }}

                @endif
            </div>


            {{-- Trạng thái thanh toán --}}
            <div class="box">
                <strong>Trạng thái thanh toán</strong>

                @if($order->payment_status === 'paid')

                    <span class="paid">
                        ✓ Đã thanh toán
                    </span>

                @else

                    <span class="unpaid">
                        Chưa thanh toán
                    </span>

                @endif
            </div>

        </div>


        {{-- Thông tin PayPal --}}
        @if($order->payment && $order->payment_method === 'paypal')

            <div class="payment-box">

                <strong>Thông tin giao dịch PayPal</strong>

                @if($order->payment->transaction_id)

                    <div class="payment-code">
                        Mã giao dịch:
                        {{ $order->payment->transaction_id }}
                    </div>

                @endif

                <div class="payment-code">
                    Trạng thái:
                    {{ $order->payment->status }}
                </div>

                @if($order->payment->paid_at)

                    <div class="payment-code">
                        Thời gian thanh toán:
                        {{ $order->payment->paid_at->format('d/m/Y H:i:s') }}
                    </div>

                @endif

            </div>

        @endif


        {{-- Ghi chú --}}
        @if($order->note)

            <div class="note">

                <strong>Ghi chú của khách hàng</strong>

                <br><br>

                {{ $order->note }}

            </div>

        @endif

    </div>


    {{-- =========================
         CẬP NHẬT TRẠNG THÁI
    ========================= --}}

    <div class="card">

        <h2>Cập nhật trạng thái đơn hàng</h2>

        <form
            method="POST"
            action="{{ route('admin.orders.update-status', $order) }}"
        >

            @csrf

            @method('PATCH')

            <select name="order_status">

                <option
                    value="pending"
                    {{ $order->order_status === 'pending' ? 'selected' : '' }}
                >
                    Chờ xác nhận
                </option>

                <option
                    value="confirmed"
                    {{ $order->order_status === 'confirmed' ? 'selected' : '' }}
                >
                    Đã xác nhận
                </option>

                <option
                    value="processing"
                    {{ $order->order_status === 'processing' ? 'selected' : '' }}
                >
                    Đang xử lý
                </option>

                <option
                    value="shipping"
                    {{ $order->order_status === 'shipping' ? 'selected' : '' }}
                >
                    Đang giao
                </option>

                <option
                    value="completed"
                    {{ $order->order_status === 'completed' ? 'selected' : '' }}
                >
                    Hoàn thành
                </option>

                <option
                    value="cancelled"
                    {{ $order->order_status === 'cancelled' ? 'selected' : '' }}
                >
                    Đã hủy
                </option>

            </select>

            <button type="submit">
                Cập nhật trạng thái
            </button>

        </form>

    </div>


    {{-- =========================
         DANH SÁCH SẢN PHẨM
    ========================= --}}

    <div class="card">

        <h2>Sản phẩm trong đơn</h2>

        @forelse($order->items as $item)

            <div class="product">

                <div>

                    <div class="product-name">
                        {{ $item->product_name }}
                    </div>

                    <div class="product-info">

                        Đơn giá:
                        {{ number_format($item->price, 0, ',', '.') }}đ

                        ×

                        {{ $item->quantity }}

                    </div>

                </div>

                <div class="product-subtotal">

                    {{ number_format($item->subtotal, 0, ',', '.') }}đ

                </div>

            </div>

        @empty

            <p>
                Không có sản phẩm trong đơn hàng.
            </p>

        @endforelse


        {{-- =========================
             TỔNG TIỀN
        ========================= --}}

        <div class="summary">

            <div class="summary-row">

                <span>
                    Tạm tính
                </span>

                <span>
                    {{ number_format($order->subtotal, 0, ',', '.') }}đ
                </span>

            </div>


            @if($order->discount > 0)

                <div class="summary-row">

                    <span>
                        Giảm giá
                    </span>

                    <span style="color:#16a34a;">
                        -{{ number_format($order->discount, 0, ',', '.') }}đ
                    </span>

                </div>

            @endif


            <div class="summary-row">

                <span>
                    Phí giao hàng
                </span>

                <span>
                    {{ number_format($order->shipping_fee, 0, ',', '.') }}đ
                </span>

            </div>


            <div class="summary-row total">

                <span>
                    Tổng cộng
                </span>

                <span>
                    {{ number_format($order->total, 0, ',', '.') }}đ
                </span>

            </div>

        </div>

    </div>


    {{-- QUAY LẠI --}}
    <a
        href="{{ route('admin.orders.index') }}"
        class="btn-back"
    >
        ← Quay lại danh sách
    </a>

</div>

</body>
</html>
