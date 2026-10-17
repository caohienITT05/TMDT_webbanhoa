<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận đơn hàng BloomGift</title>
    <style>
        body { margin: 0; padding: 24px 12px; background: #fcfaf8; color: #352932; font-family: Arial, Helvetica, sans-serif; }
        .email-wrapper { width: 100%; max-width: 600px; margin: 0 auto; overflow: hidden; background: #ffffff; border: 1px solid #eadfdb; border-radius: 10px; }
        .email-header { padding: 32px 28px; background: #2f1d28; color: #ffffff; text-align: center; }
        .brand { margin: 0; font-family: Georgia, serif; font-size: 28px; font-weight: 700; }
        .email-body { padding: 32px 28px; line-height: 1.7; }
        h1 { margin: 0 0 18px; color: #2f1d28; font-family: Georgia, serif; font-size: 25px; line-height: 1.3; }
        p { margin: 0 0 16px; color: #5f555a; font-size: 15px; }
        .summary { margin: 24px 0; padding: 18px; border-radius: 8px; background: #f9f0ef; }
        .summary p { margin: 0 0 8px; }
        .summary p:last-child { margin-bottom: 0; }
        .items { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .items th, .items td { padding: 10px 0; border-bottom: 1px solid #eadfdb; font-size: 14px; text-align: left; }
        .items th:last-child, .items td:last-child { text-align: right; }
        .total { margin-top: 18px; color: #7d2743; font-size: 18px; font-weight: 700; text-align: right; }
        .email-footer { padding: 21px 28px; border-top: 1px solid #eadfdb; background: #fcfaf8; text-align: center; }
        .email-footer p { margin: 0; color: #766a70; font-size: 12px; }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-header"><p class="brand">BloomGift</p></div>
        <div class="email-body">
            <h1>BloomGift đã nhận đơn hàng của bạn</h1>
            <p>Chào bạn,</p>
            <p>Cảm ơn bạn đã đặt hoa tại BloomGift. Shop sẽ sớm xác nhận và cập nhật trạng thái đơn hàng.</p>

            <div class="summary">
                <p><strong>Mã đơn:</strong> #{{ $order->order_code ?? $order->id }}</p>
                <p><strong>Người nhận:</strong> {{ $order->recipient_name }}</p>
                <p><strong>Ngày giao:</strong> {{ optional($order->delivery_date)->format('d/m/Y') ?? 'Đang cập nhật' }}</p>
                <p><strong>Khung giờ:</strong> {{ $order->deliverySlot?->name ?? 'Đang cập nhật' }}</p>
                <p><strong>Thanh toán:</strong> {{ strtoupper($order->payment_method ?? 'cod') }}</p>
            </div>

            <table class="items" role="presentation">
                <thead><tr><th>Sản phẩm</th><th>SL</th><th>Thành tiền</th></tr></thead>
                <tbody>
                    @foreach ($order->items as $item)
                        <tr>
                            <td>{{ $item->product_name }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ number_format((float) $item->subtotal, 0, ',', '.') }} đ</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p class="total">Tổng thanh toán: {{ number_format((float) $order->total, 0, ',', '.') }} đ</p>
            @if ($guestOrderUrl ?? null)
                <p><a href="{{ $guestOrderUrl }}" style="color:#9d3158;font-weight:700">Theo dõi đơn hàng và thanh toán an toàn</a></p>
                <p style="font-size:12px">Liên kết theo dõi đơn có hiệu lực trong 30 ngày. Hãy giữ riêng email này.</p>
            @endif
        </div>
        <div class="email-footer"><p><strong>BloomGift</strong> · Gửi trọn lời thương trong từng món quà.</p></div>
    </div>
</body>
</html>
