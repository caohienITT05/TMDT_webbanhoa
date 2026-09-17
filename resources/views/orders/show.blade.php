<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Chi tiết đơn hàng - BloomGift</title>

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
            max-width: 1000px;
            margin: 40px auto;
        }

        .logo {
            text-align: center;
            color: #d92d6b;
            font-size: 38px;
            font-weight: bold;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            border-radius: 16px;
            padding: 28px;
            margin-bottom: 24px;
            box-shadow: 0 5px 20px rgba(0,0,0,.08);
        }

        h2 {
            margin-top: 0;
            color: #333;
        }

        .order-header {
            text-align: center;
        }

        .order-code {
            font-size: 25px;
            font-weight: bold;
        }

        .badge {
            display: inline-block;
            padding: 9px 18px;
            border-radius: 20px;
            background: #ffe0eb;
            color: #d92d6b;
            font-weight: bold;
            margin-top: 12px;
        }

        .success {
            background: #d1fae5;
            color: #047857;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }

        .error {
            background: #fee2e2;
            color: #b91c1c;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .info-box {
            background: #fafafa;
            padding: 16px;
            border-radius: 10px;
        }

        .info-box strong {
            display: block;
            margin-bottom: 7px;
        }

        .timeline {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin: 35px 0 15px;
        }

        .timeline::before {
            content: "";
            position: absolute;
            top: 18px;
            left: 8%;
            right: 8%;
            height: 4px;
            background: #eee;
            z-index: 0;
        }

        .step {
            position: relative;
            z-index: 1;
            text-align: center;
            width: 18%;
        }

        .dot {
            width: 36px;
            height: 36px;
            margin: 0 auto 10px;
            border-radius: 50%;
            background: #eee;
            border: 4px solid white;
            box-shadow: 0 0 0 2px #eee;
        }

        .step.active .dot {
            background: #d92d6b;
            box-shadow: 0 0 0 2px #d92d6b;
        }

        .step.current .dot {
            background: #16a34a;
            box-shadow: 0 0 0 2px #16a34a;
        }

        .step-title {
            font-size: 14px;
            font-weight: bold;
        }

        .product {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .total {
            display: flex;
            justify-content: space-between;
            padding-top: 20px;
            font-size: 22px;
            font-weight: bold;
            color: #d92d6b;
        }

        .buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 25px;
        }

        .btn {
            border: none;
            border-radius: 8px;
            padding: 12px 20px;
            text-decoration: none;
            cursor: pointer;
            font-size: 16px;
        }

        .btn-back {
            background: #d92d6b;
            color: white;
        }

        .btn-cancel {
            background: #dc2626;
            color: white;
        }

        @media (max-width: 700px) {
            .info-grid {
                grid-template-columns: 1fr;
            }

            .timeline {
                flex-direction: column;
                gap: 20px;
            }

            .timeline::before {
                display: none;
            }

            .step {
                width: 100%;
                display: flex;
                align-items: center;
                gap: 15px;
                text-align: left;
            }

            .dot {
                margin: 0;
                flex-shrink: 0;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <div class="logo">
        🌸 BloomGift
    </div>

    {{-- Thông báo --}}
    @if(session('success'))
        <div class="success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="error">
            {{ session('error') }}
        </div>
    @endif

    {{-- Header đơn --}}
    <div class="card order-header">

        <div class="order-code">
            Mã đơn hàng: {{ $order->order_code }}
        </div>

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

        <div class="badge">
            {{ $statusLabels[$order->order_status] ?? $order->order_status }}
        </div>

    </div>

    {{-- Theo dõi trạng thái --}}
    <div class="card">

        <h2>📦 Theo dõi đơn hàng</h2>

        @php
            $steps = [
                'pending' => 'Chờ xác nhận',
                'confirmed' => 'Đã xác nhận',
                'processing' => 'Đang xử lý',
                'shipping' => 'Đang giao',
                'completed' => 'Hoàn thành',
            ];

            $stepKeys = array_keys($steps);

            $currentIndex = array_search(
                $order->order_status,
                $stepKeys
            );

            if ($currentIndex === false) {
                $currentIndex = -1;
            }
        @endphp

        @if($order->order_status === 'cancelled')

            <div class="error">
                Đơn hàng đã được hủy.
            </div>

        @else

            <div class="timeline">

                @foreach($steps as $key => $label)

                    @php
                        $index = array_search($key, $stepKeys);

                        $active = $index <= $currentIndex;
                        $current = $key === $order->order_status;
                    @endphp

                    <div class="step
                        {{ $active ? 'active' : '' }}
                        {{ $current ? 'current' : '' }}">

                        <div class="dot"></div>

                        <div class="step-title">
                            {{ $label }}
                        </div>

                    </div>

                @endforeach

            </div>

        @endif

    </div>

    {{-- Người nhận --}}
    <div class="card">

        <h2>Thông tin người nhận</h2>

        <div class="info-grid">

            <div class="info-box">
                <strong>Họ tên</strong>
                {{ $order->recipient_name }}
            </div>

            <div class="info-box">
                <strong>Số điện thoại</strong>
                {{ $order->recipient_phone }}
            </div>

            <div class="info-box">
                <strong>Địa chỉ</strong>
                {{ $order->recipient_address }}
            </div>

            <div class="info-box">
                <strong>Ngày giao</strong>
                {{ $order->delivery_date?->format('d/m/Y') }}
            </div>

            <div class="info-box">
                <strong>Khung giờ</strong>

                @if($order->deliverySlot)
                    {{ $order->deliverySlot->name }}
                    ({{ $order->deliverySlot->start_time }} -
                    {{ $order->deliverySlot->end_time }})
                @else
                    Chưa chọn
                @endif
            </div>

            <div class="info-box">
                <strong>Thanh toán</strong>

                @if($order->payment_method === 'paypal')
                    PayPal
                @else
                    COD
                @endif
            </div>

            <div class="info-box">
                <strong>Trạng thái thanh toán</strong>

                @if($order->payment_status === 'paid')
                    <span style="color:#16a34a;font-weight:bold;">
                        Đã thanh toán
                    </span>
                @else
                    <span>
                        Chưa thanh toán
                    </span>
                @endif
            </div>

        </div>

    </div>

    {{-- Sản phẩm --}}
    <div class="card">

        <h2>Sản phẩm</h2>

        @foreach($order->items as $item)

            <div class="product">

                <div>
                    <strong>
                        {{ $item->product_name }}
                    </strong>

                    <br>

                    {{ number_format($item->price, 0, ',', '.') }}đ
                    × {{ $item->quantity }}
                </div>

                <div>
                    {{ number_format($item->subtotal, 0, ',', '.') }}đ
                </div>

            </div>

        @endforeach

        <div class="product">
            <span>Tạm tính</span>
            <span>
                {{ number_format($order->subtotal, 0, ',', '.') }}đ
            </span>
        </div>

        <div class="product">
            <span>Giảm giá</span>
            <span>
                -{{ number_format($order->discount, 0, ',', '.') }}đ
            </span>
        </div>

        <div class="product">
            <span>Phí giao hàng</span>
            <span>
                {{ number_format($order->shipping_fee, 0, ',', '.') }}đ
            </span>
        </div>

        <div class="total">
            <span>Tổng cộng</span>
            <span>
                {{ number_format($order->total, 0, ',', '.') }}đ
            </span>
        </div>

    </div>

    {{-- Nút --}}
    <div class="buttons">

        <a href="{{ route('orders.index') }}"
           class="btn btn-back">
            ← Danh sách đơn hàng
        </a>

        @if(in_array($order->order_status, ['pending', 'confirmed']))

            <form method="POST"
                  action="{{ route('orders.cancel', $order) }}"
                  onsubmit="return confirm('Bạn có chắc muốn hủy đơn hàng này?');">

                @csrf
                @method('PATCH')

                <button type="submit"
                        class="btn btn-cancel">
                    Hủy đơn hàng
                </button>

            </form>

        @endif

    </div>

</div>

</body>
</html>
