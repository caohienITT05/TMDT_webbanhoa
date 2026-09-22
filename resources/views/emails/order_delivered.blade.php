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
        }

        .title {
            color: #16a34a;
            font-size: 22px;
            margin-bottom: 5px;
        }

        .gift-box {
            background: #fdf2f8;
            border: 2px dashed #f43f5e;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            margin: 20px 0;
        }

        .coupon-code {
            font-size: 20px;
            font-weight: bold;
            color: #e11d48;
            letter-spacing: 2px;
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
            <h2 class="title">🎉 GIAO HOA THÀNH CÔNG!</h2>
            <p style="color: #666; font-size: 14px;">Bó hoa tươi thắm đã được trao tận tay người nhận.</p>
        </div>

        <p>Xin chào <strong>{{ $order->user->name ?? $order->recipient_name }}</strong>,</p>
        <p>BloomGift xin thông báo đơn hàng <strong>#{{ $order->order_code ?? $order->id }}</strong> của bạn đã hoàn tất
            giao hàng thành công.</p>
        <p>Hy vọng bó hoa đã mang lại niềm vui trọn vẹn cho bạn và người thương!</p>

        <!-- Tặng voucher tri ân kích thích mua lại -->
        <div class="gift-box">
            <p style="margin: 0 0 5px; font-weight: bold; color: #9f1239;">🎁 MÓN QUÀ TRI ÂN TỪ BLOOMGIFT</p>
            <p style="font-size: 13px; margin: 0 0 10px; color: #666;">Tặng bạn mã ưu đãi giảm 10% cho lần đặt hoa tiếp
                theo:</p>
            <span class="coupon-code">BLOOMTHANKS10</span>
        </div>

        <div style="text-align: center; margin-top: 20px;">
            <a href="http://127.0.0.1:8000"
                style="display: inline-block; background: #e11d48; color: #fff; padding: 10px 24px; text-decoration: none; border-radius: 20px; font-weight: bold; font-size: 13px;">Khám
                phá các mẫu hoa mới</a>
        </div>

        <div class="footer">
            <p>Mọi thắc mắc và đóng góp ý kiến xin gửi về hotline: 034.553.4xxx</p>
            <p>© 2026 BloomGift Florist. All rights reserved.</p>
        </div>
    </div>
</body>

</html>