<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nhắc dịp lễ BloomGift</title>
    <style>
        body { margin: 0; padding: 24px 12px; background: #fcfaf8; color: #352932; font-family: Arial, Helvetica, sans-serif; }
        .email-wrapper { width: 100%; max-width: 600px; margin: 0 auto; overflow: hidden; background: #ffffff; border: 1px solid #eadfdb; border-radius: 10px; }
        .email-header { padding: 34px 28px 30px; background: #2f1d28; color: #ffffff; text-align: center; }
        .brand { margin: 0; font-family: Georgia, serif; font-size: 28px; font-weight: 700; letter-spacing: -.4px; }
        .brand-mark { display: inline-block; width: 10px; height: 10px; margin-right: 7px; border-radius: 50%; background: #d99aaf; vertical-align: middle; }
        .eyebrow { margin: 13px 0 0; color: #ead7dc; font-size: 11px; font-weight: 700; letter-spacing: 1.6px; text-transform: uppercase; }
        .email-body { padding: 34px 28px; line-height: 1.7; }
        h1 { margin: 0 0 18px; color: #2f1d28; font-family: Georgia, serif; font-size: 25px; line-height: 1.3; }
        p { margin: 0 0 16px; color: #5f555a; font-size: 15px; }
        .voucher-card { margin: 28px 0; padding: 22px; border: 1px dashed #b85b78; border-radius: 8px; background: #f9f0ef; text-align: center; }
        .voucher-title { margin: 0; color: #7d2743; font-size: 11px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; }
        .voucher-code { margin: 10px 0 7px; color: #9f3657; font-size: 28px; font-weight: 800; letter-spacing: 2px; }
        .voucher-desc { margin: 0; color: #766a70; font-size: 12px; line-height: 1.6; }
        .cta-wrap { margin: 28px 0 4px; text-align: center; }
        .btn-cta { display: inline-block; padding: 13px 22px; border-radius: 6px; background: #9f3657; color: #ffffff !important; font-size: 14px; font-weight: 700; text-decoration: none; }
        .email-footer { padding: 21px 28px; border-top: 1px solid #eadfdb; background: #fcfaf8; text-align: center; }
        .email-footer p { margin: 0; color: #766a70; font-size: 12px; line-height: 1.6; }
        @media only screen and (max-width: 480px) { body { padding: 0; } .email-wrapper { border-radius: 0; border-left: 0; border-right: 0; } .email-header, .email-body { padding-left: 22px; padding-right: 22px; } }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-header">
            <p class="brand"><span class="brand-mark"></span>BloomGift</p>
            <p class="eyebrow">Hoa & quà tặng cho những dịp đặc biệt</p>
        </div>
        <div class="email-body">
            <h1>Dịp lễ {{ $holidayName }} đang đến gần</h1>
            <p>Xin chào <strong>{{ $user->name ?? 'bạn' }}</strong>,</p>
            <p>Những ngày lễ đặc biệt là dịp ý nghĩa để gửi gắm tình cảm và sự trân trọng qua một món quà thật tinh tế.</p>
            <p>BloomGift gợi ý bạn đặt lịch sớm để chủ động chọn sản phẩm, ngày và khung giờ giao phù hợp.</p>
            <div class="voucher-card">
                <p class="voucher-title">Ưu đãi dành cho bạn</p>
                <div class="voucher-code">{{ $voucherCode }}</div>
                <p class="voucher-desc">Nhập mã này tại bước thanh toán để nhận ưu đãi cho đơn hàng.</p>
            </div>
            <div class="cta-wrap"><a href="{{ config('app.url') }}" class="btn-cta">Khám phá bộ sưu tập</a></div>
        </div>
        <div class="email-footer"><p><strong>BloomGift</strong> · Gửi trọn lời thương trong từng món quà.</p></div>
    </div>
</body>
</html>
