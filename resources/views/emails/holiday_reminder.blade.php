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

        .banner {
            background: linear-gradient(135deg, #fb7185, #e11d48);
            color: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
        }

        .voucher-badge {
            display: inline-block;
            background: #fff;
            color: #e11d48;
            padding: 10px 22px;
            border-radius: 25px;
            font-weight: bold;
            font-size: 18px;
            margin: 15px 0;
            border: 2px dashed #f43f5e;
            letter-spacing: 2px;
        }

        .btn-shop {
            display: inline-block;
            background: #e11d48;
            color: white;
            padding: 12px 28px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: bold;
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
        <div class="banner">
            <h1 style="margin: 0; font-size: 24px;">🌸 BloomGift Florist</h1>
            <p style="margin: 8px 0 0; font-size: 14px; opacity: 0.95;">Sắp đến {{ $holidayName }} rồi bạn ơi!</p>
        </div>

        <div style="padding: 20px 0;">
            <p>Xin chào <strong>{{ $user->name ?? 'Bạn thương' }}</strong>,</p>
            <p>Dịp <strong>{{ $holidayName }}</strong> đang đến rất gần. Hãy để BloomGift giúp bạn trao gửi thông điệp
                yêu thương qua những bó hoa tươi rạng rỡ nhất.</p>

            <div style="text-align: center; background: #fff5f7; padding: 15px; border-radius: 12px; margin: 20px 0;">
                <p style="margin: 0; color: #be123c; font-weight: bold; font-size: 13px;">MÃ ƯU ĐÃI ĐẶT HOA SỚM (EARLY
                    BIRD):</p>
                <div class="voucher-badge">{{ $voucherCode }}</div>
                <p style="margin: 0; font-size: 12px; color: #666;">Nhập mã trên tại giỏ hàng để nhận giảm giá đặc biệt.
                </p>
            </div>

            <div style="text-align: center; margin-top: 20px;">
                <a href="http://127.0.0.1:8000/san-pham" class="btn-shop">Xem Mẫu Hoa Dịp Lễ &rarr;</a>
            </div>
        </div>

        <div class="footer">
            <p>Hotline hỗ trợ: 034.553.4xxx | BloomGift - Trao gửi khoảnh khắc đẹp</p>
        </div>
    </div>
</body>

</html>