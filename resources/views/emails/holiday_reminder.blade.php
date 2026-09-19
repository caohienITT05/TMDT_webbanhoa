<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nhắc dịp lễ BloomGift</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #fff0f5;
            margin: 0;
            padding: 20px;
            color: #333333;
        }

        .email-wrapper {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #ffe5ec;
            box-shadow: 0 4px 15px rgba(225, 29, 72, 0.08);
        }

        .email-header {
            background: linear-gradient(135deg, #fb7185, #e11d48);
            padding: 30px 20px;
            text-align: center;
            color: #ffffff;
        }

        .email-header h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
        }

        .email-header p {
            margin: 8px 0 0;
            font-size: 14px;
            opacity: 0.95;
        }

        .email-body {
            padding: 30px 25px;
            line-height: 1.6;
        }

        .voucher-card {
            background-color: #fff0f3;
            border: 2px dashed #f43f5e;
            border-radius: 12px;
            padding: 18px;
            text-align: center;
            margin: 25px 0;
        }

        .voucher-title {
            font-size: 13px;
            color: #881337;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .voucher-code {
            font-size: 28px;
            font-weight: 800;
            color: #e11d48;
            margin: 8px 0;
            letter-spacing: 2px;
        }

        .voucher-desc {
            font-size: 12px;
            color: #4b5563;
        }

        .btn-cta {
            display: inline-block;
            background-color: #e11d48;
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 9999px;
            font-weight: 700;
            font-size: 14px;
            box-shadow: 0 4px 10px rgba(225, 29, 72, 0.3);
        }

        .email-footer {
            background-color: #fffafb;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #ffe5ec;
            font-size: 12px;
            color: #6b7280;
        }
    </style>
</head>

<body>
    <div class="email-wrapper">
        <div class="email-header">
            <h1>🌸 Tiệm Hoa Tươi BloomGift</h1>
            <p>Trao gửi yêu thương - Trọn vẹn từng khoảnh khắc</p>
        </div>

        <div class="email-body">
            <h2 style="color: #9f1239; font-size: 18px; margin-top: 0;">
                💐 Dịp Lễ {{ $holidayName }} Đang Đến Rất Gần!
            </h2>
            <p>Xin chào <strong>{{ $user->name ?? 'Bạn thân mến' }}</strong>,</p>
            <p>
                Những ngày lễ đặc biệt là thời điểm ý nghĩa nhất để chúng ta gửi gắm tình cảm, sự trân trọng đến những
                người phụ nữ yêu thương qua những đóa hoa tươi thắm.
            </p>
            <p>
                Để các nghệ nhân cắm hoa chuẩn bị chu đáo và đảm bảo giao hoa đúng khung giờ bạn mong muốn, BloomGift
                gợi ý bạn nên đặt lịch sớm ngay từ hôm nay.
            </p>

            <div class="voucher-card">
                <div class="voucher-title">Món quà ưu đãi đặt sớm dành tặng riêng bạn</div>
                <div class="voucher-code">{{ $voucherCode }}</div>
                <div class="voucher-desc">Nhập mã này tại bước thanh toán để nhận ngay chiết khấu cho đơn hoa</div>
            </div>

            <div style="text-align: center; margin: 30px 0 10px;">
                <a href="http://127.0.0.1:8000" class="btn-cta">
                    🌷 Khám Phá Bộ Sưu Tập Hoa
                </a>
            </div>
        </div>

        <div class="email-footer">
            <p style="margin: 0 0 5px;"><strong>BloomGift - Cửa Hàng Hoa Tươi & Quà Tặng Trực Tuyến</strong></p>
            <p style="margin: 0;">Hotline hỗ trợ: 0988.123.456 | Email: support@bloomgift.vn</p>
        </div>
    </div>
</body>

</html>