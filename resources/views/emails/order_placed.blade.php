<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #fff1f5;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        .box {
            max-width: 600px;
            margin: auto;
            background: #fff;
            border-radius: 16px;
            padding: 30px;
            border: 1px solid #ffd1dc;
        }

        .header {
            text-align: center;
            border-bottom: 2px dashed #f43f5e;
            padding-bottom: 15px;
        }

        .title {
            color: #e11d48;
            margin: 5px 0;
            font-size: 22px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 13px;
        }

        .info-table td {
            padding: 8px;
            border-bottom: 1px solid #fce7f3;
        }

        .total {
            font-size: 18px;
            font-weight: bold;
            color: #e11d48;
            text-align: right;
            margin-top: 15px;
        }

        .footer {
            text-align: center;
            font-size: 12px;
            color: #888;
            margin-top: 25px;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }
    </style>
</head>

<body>
    <div class="box">
        <div class="header">
            <h2 class="title">🌸 BLOOMGIFT FLORIST</h2>
            <p style="margin: 0; color: #666; font-size: 13px;">Cảm ơn bạn đã lựa chọn trao gửi yêu thương!</p>
        </div>

        <p style="margin-top: 20px;">Xin chào <strong>{{ $order->recipient_name }}</strong>,</p>
        <p>Đơn đặt hoa <strong>#{{ $order->order_code ?? $order->id }}</strong> của bạn đã được hệ thống tiếp nhận thành
            công và đang được chuẩn bị.</p>

        <table class="info-table">
            <tr>
                <td><strong>Ngày giao hoa:</strong></td>
                <td>{{ $order->delivery_date ? \Carbon\Carbon::parse($order->delivery_date)->format('d/m/Y') : 'Tiêu chuẩn' }}
                </td>
            </tr>
            <tr>
                <td><strong>Khung giờ:</strong></td>
                <td>{{ $order->deliverySlot->name ?? ($order->deliverySlot->start_time ?? 'Giờ hành chính') }}</td>
            </tr>
            <tr>
                <td><strong>Địa chỉ giao:</strong></td>
                <td>{{ $order->recipient_address }}</td>
            </tr>
            <tr>
                <td><strong>Số điện thoại:</strong></td>
                <td>{{ $order->recipient_phone }}</td>
            </tr>
            <tr>
                <td><strong>Phương thức:</strong></td>
                <td>{{ strtoupper($order->payment_method) }}
                    ({{ $order->payment_status === 'paid' ? 'Đã thanh toán' : 'Thanh toán khi nhận' }})</td>
            </tr>
            @if($order->note)
                <tr>
                    <td><strong>Lời chúc thiệp:</strong></td>
                    <td style="font-style: italic; color: #be123c;">"{{ $order->note }}"</td>
                </tr>
            @endif
        </table>

        <div class="total">
            Tổng thanh toán: {{ number_format($order->total ?? $order->total_amount, 0, ',', '.') }} đ
        </div>

        <div class="footer">
            <p>Hotline hỗ trợ: 034.553.4xxx | Website: BloomGift</p>
            <p>BloomGift - Mang những đóa hoa tươi thắm đến mọi khoảnh khắc đẹp!</p>
        </div>
    </div>
</body>

</html>