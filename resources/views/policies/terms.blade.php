<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Điều kiện giao dịch chung - BloomGift</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #fffafb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
        }

        .policy-container {
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(232, 93, 117, 0.08);
            border: 1px solid #ffe5ec;
        }

        h1 {
            color: #c9184a;
            font-weight: 700;
            margin-bottom: 25px;
            border-bottom: 2px solid #ffccd5;
            padding-bottom: 12px;
        }

        h4 {
            color: #800f2f;
            margin-top: 25px;
            font-weight: 600;
        }

        p,
        li {
            line-height: 1.7;
            font-size: 15px;
            color: #4a4a4a;
        }

        .info-box {
            background: #fff0f3;
            border-left: 4px solid #ff4d6d;
            padding: 15px 20px;
            border-radius: 0 8px 8px 0;
            margin-bottom: 25px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="policy-container">
            <a href="{{ route('home') }}" class="btn btn-sm btn-outline-danger mb-3"><i class="bi bi-arrow-left"></i>
                Quay lại cửa hàng</a>

            <h1>Điều kiện Giao dịch Chung & Thông tin Người bán</h1>

            <div class="info-box">
                <h5 class="fw-bold text-danger mb-2">🌸 THÔNG TIN ĐƠN VỊ BÁN HÀNG (BLOOMGIFT SHOP)</h5>
                <ul class="list-unstyled mb-0">
                    <li><strong>Đơn vị sở hữu:</strong> Dự án Cửa hàng Hoa tươi BloomGift</li>
                    <li><strong>Địa chỉ:</strong> Hà Đông, Hà Nội, Việt Nam</li>
                    <li><strong>Hotline hỗ trợ:</strong> 0988.123.456 (8h00 - 21h00 hàng ngày)</li>
                    <li><strong>Email liên hệ:</strong> support@bloomgift.local</li>
                </ul>
            </div>

            <h4>1. Nguyên tắc chung</h4>
            <p>Website BloomGift cung cấp dịch vụ đặt hoa tươi, hoa thiết kế theo yêu cầu, giao hàng tận nơi và thiệp
                chúc mừng. Khách hàng tham gia giao dịch trên website bao gồm cá nhân có đầy đủ năng lực hành vi dân sự.
            </p>

            <h4>2. Quy trình giao kết hợp đồng điện tử</h4>
            <p>Hợp đồng mua bán được xác lập qua các bước rõ ràng:</p>
            <ol>
                <li>Khách hàng chọn sản phẩm hoa, chọn ngày và khung giờ nhận hoa.</li>
                <li>Khách hàng điền thông tin người nhận, ghi chú thiệp chúc mừng.</li>
                <li>Khách hàng kiểm tra tóm tắt đơn hàng, áp mã khuyến mại (nếu có).</li>
                <li>Chọn phương thức thanh toán (COD hoặc PayPal Sandbox) và nhấn <strong>"Đặt hàng ngay"</strong> để
                    xác nhận giao kết.</li>
                <li>Hệ thống gửi mã đơn hàng và lưu vết giao dịch tại mục Chi tiết đơn hàng.</li>
            </ol>

            <h4>3. Giá cả và chi phí giao nhận</h4>
            <p>Tất cả giá hoa niêm yết trên website đã bao gồm chi phí đóng gói tiêu chuẩn và thiệp chúc kèm theo. Phí
                giao hàng được tính minh bạch theo từng khu vực trước khi khách bấm đặt đơn.</p>

            <h4>4. Thời gian giao nhận</h4>
            <p>Cửa hàng hỗ trợ giao theo các khung giờ đăng ký trong ngày từ 08:00 đến 21:00. Trường hợp có sự cố thời
                tiết hoặc sự kiện bất khả kháng, BloomGift sẽ liên hệ báo trước tối thiểu 60 phút.</p>
        </div>
    </div>
</body>

</html>