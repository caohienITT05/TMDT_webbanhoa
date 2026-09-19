<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chính sách bảo vệ dữ liệu cá nhân - BloomGift</title>
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
    </style>
</head>

<body>
    <div class="container">
        <div class="policy-container">
            <a href="{{ route('home') }}" class="btn btn-sm btn-outline-danger mb-3"><i class="bi bi-arrow-left"></i>
                Quay lại cửa hàng</a>

            <h1>Chính sách Bảo vệ Dữ liệu Cá nhân</h1>
            <p class="text-muted fst-italic">Tuân thủ theo Nghị định số 13/2023/NĐ-CP về Bảo vệ dữ liệu cá nhân của
                Chính phủ.</p>

            <h4>1. Loại dữ liệu thu thập</h4>
            <p>Để phục vụ quá trình xử lý đơn hàng và giao nhận hoa, BloomGift chỉ thu thập các trường thông tin cần
                thiết:</p>
            <ul>
                <li>Họ và tên của người đặt hàng và người nhận hoa.</li>
                <li>Số điện thoại và địa chỉ giao nhận hoa chi tiết.</li>
                <li>Địa chỉ thư điện tử (Email) để gửi thông báo xác nhận đơn và nhắc dịp lễ.</li>
                <li>Thông tin lịch sử đơn hàng và mã giao dịch thanh toán trực tuyến.</li>
            </ul>

            <h4>2. Mục đích xử lý dữ liệu</h4>
            <ul>
                <li>Thực hiện liên lạc xác nhận và điều phối người giao hàng đến đúng địa chỉ và khung giờ.</li>
                <li>Xử lý thanh toán, hóa đơn và giải quyết các khiếu nại phát sinh.</li>
                <li>Gửi email thông báo về trạng thái chuẩn bị đơn hoa hoặc chương trình khuyến mại (khi được đồng ý).
                </li>
            </ul>

            <h4>3. Cam kết an toàn và bảo mật thông tin</h4>
            <p>BloomGift áp dụng các biện pháp kỹ thuật tiêu chuẩn (mã hóa mật khẩu một chiều, phòng chống SQL
                Injection, CSRF Token và bảo mật phiên Session) để ngăn chặn truy cập trái phép. Tuyệt đối không chia
                sẻ, bán hoặc chuyển giao dữ liệu khách hàng cho bên thứ ba vì mục đích thương mại.</p>

            <h4>4. Quyền của chủ thể dữ liệu</h4>
            <p>Khách hàng có toàn quyền xem, chỉnh sửa thông tin cá nhân trong mục <strong>Tài khoản của tôi</strong>
                hoặc gửi yêu cầu xóa tài khoản khỏi hệ thống bất cứ lúc nào qua email hỗ trợ.</p>
        </div>
    </div>
</body>

</html>