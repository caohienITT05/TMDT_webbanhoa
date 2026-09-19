<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn hàng của tôi - BloomGift</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-rose: #e11d48;
            --soft-rose: #fff0f3;
            --border-rose: #ffe4e6;
        }
        body { background-color: #fffafb; font-family: system-ui, -apple-system, sans-serif; color: #374151; }
        .order-card { background: #fff; border-radius: 16px; border: 1px solid var(--border-rose); margin-bottom: 20px; box-shadow: 0 4px 15px rgba(225, 29, 72, 0.04); overflow: hidden; }
        .status-badge { padding: 6px 12px; border-radius: 9999px; font-size: 12px; font-weight: 700; text-transform: uppercase; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-confirmed { background: #e0e7ff; color: #3730a3; }
        .status-preparing { background: #fce7f3; color: #9d174d; }
        .status-shipping { background: #e0f2fe; color: #0369a1; }
        .status-completed { background: #dcfce7; color: #166534; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>

<div class="container py-5" style="max-width: 900px;">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h2 class="h4 fw-bold text-dark mb-1">🌸 Đơn đặt hoa của bạn</h2>
            <p class="text-muted small mb-0">Theo dõi trạng thái chuẩn bị hoa, cắm hoa và lịch giao tận nơi</p>
        </div>
        <a href="{{ url('/') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
            &larr; Về trang chủ
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-3 py-2 small mb-4">
            ✅ {{ session('success') }}
        </div>
    @endif

    @if($orders->isEmpty())
        <div class="order-card p-5 text-center">
            <div class="display-3 mb-3">🌷</div>
            <h5 class="fw-bold text-secondary">Bạn chưa có đơn đặt hoa nào</h5>
            <p class="text-muted small">Hãy chọn những mẫu hoa tươi thắm nhất gửi tặng người thương nhé!</p>
            <a href="{{ route('products.index') }}" class="btn text-white rounded-pill px-4 py-2 mt-2" style="background-color: var(--primary-rose);">
                Khám phá các mẫu hoa
            </a>
        </div>
    @else
        @foreach($orders as $order)
            <div class="order-card">
                <!-- Header của Card -->
                <div class="p-3 d-flex justify-content-between align-items-center border-bottom" style="background-color: var(--soft-rose);">
                    <div>
                        <span class="fw-bold text-dark me-2">Mã đơn: #{{ $order->order_code ?? ('BG-' . $order->id) }}</span>
                        <span class="text-muted small">| Ngày đặt: {{ $order->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div>
                        @php
                            $status = strtoupper($order->status ?? $order->order_status ?? 'PENDING');
                        @endphp
                        @if($status === 'PENDING')
                            <span class="status-badge status-pending">⏳ Chờ tiệm duyệt</span>
                        @elseif($status === 'CONFIRMED')
                            <span class="status-badge status-confirmed">✓ Đã xác nhận</span>
                        @elseif($status === 'PREPARING')
                            <span class="status-badge status-preparing">💐 Đang cắm hoa</span>
                        @elseif($status === 'SHIPPING')
                            <span class="status-badge status-shipping">🚚 Đang giao hoa</span>
                        @elseif($status === 'COMPLETED' || $status === 'DELIVERED')
                            <span class="status-badge status-completed">🎉 Giao thành công</span>
                        @else
                            <span class="status-badge status-cancelled">✕ Đã hủy</span>
                        @endif
                    </div>
                </div>

                <!-- Thân đơn hàng -->
                <div class="p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6 small">
                            <p class="mb-1 text-secondary">👤 <strong>Người nhận:</strong> {{ $order->recipient_name }} ({{ $order->recipient_phone }})</p>
                            <p class="mb-1 text-secondary">📍 <strong>Địa chỉ giao:</strong> {{ $order->recipient_address }}</p>
                            <p class="mb-0 text-secondary">⏰ <strong>Lịch giao hoa:</strong> {{ \Carbon\Carbon::parse($order->delivery_date)->format('d/m/Y') }} ({{ $order->deliverySlot->name ?? 'Tiêu chuẩn' }})</p>
                        </div>
                        <div class="col-md-6 small text-md-end">
                            <p class="mb-1 text-secondary">Phương thức: <strong>{{ strtoupper($order->payment_method) }}</strong></p>
                            <p class="mb-1">Trạng thái thanh toán: 
                                <span class="badge {{ $order->payment_status === 'paid' ? 'bg-success' : 'bg-warning text-dark' }}">
                                    {{ $order->payment_status === 'paid' ? 'Đã thanh toán' : 'Chưa thanh toán' }}
                                </span>
                            </p>
                            @if($order->note)
                                <p class="mb-0 text-muted fst-italic">{{ $order->note }}</p>
                            @endif
                        </div>
                    </div>

                    <!-- Danh sách sản phẩm hoa -->
                    <div class="border-top pt-3">
                        @foreach($order->orderItems as $item)
                            <div class="d-flex justify-content-between align-items-center py-1 small">
                                <span>🌸 {{ $item->product_name ?? ($item->product->name ?? 'Hoa tươi') }} &times; <strong>{{ $item->quantity }}</strong></span>
                                <span class="fw-semibold text-dark">{{ number_format($item->subtotal ?? ($item->price * $item->quantity), 0, ',', '.') }} đ</span>
                            </div>
                        @endforeach
                    </div>

                    <!-- Tổng tiền thanh toán -->
                    <div class="border-top mt-3 pt-3 d-flex justify-content-between align-items-center">
                        <span class="small text-muted">Tổng thanh toán (gồm ship & ưu đãi):</span>
                        <span class="fs-5 fw-bold text-danger">{{ number_format($order->total ?? $order->total_amount, 0, ',', '.') }} đ</span>
                    </div>
                </div>
            </div>
        @endforeach

        <div class="d-flex justify-content-center mt-4">
            {{ $orders->links() }}
        </div>
    @endif
</div>

</body>
</html>