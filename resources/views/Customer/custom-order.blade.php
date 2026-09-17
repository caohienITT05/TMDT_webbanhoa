<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Đặt hoa theo yêu cầu - BloomGift</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #fff7fa;
            color: #573c49;
        }

        .custom-order-page {
            min-height: 100vh;
            padding: 40px 20px 60px;
        }

        .page-header {
            max-width: 1100px;
            margin: 0 auto 35px;
            text-align: center;
        }

        .page-header a {
            display: inline-block;
            margin-bottom: 20px;
            color: #d6537b;
            font-size: 15px;
            font-weight: 700;
            text-decoration: none;
        }

        .page-header a:hover {
            color: #b83f65;
        }

        .page-header h1 {
            margin-bottom: 12px;
            color: #573c49;
            font-size: 34px;
        }

        .page-header p {
            color: #806b75;
            font-size: 16px;
            line-height: 1.6;
        }

        .custom-order-layout {
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 0.9fr 1.1fr;
            gap: 35px;
            align-items: stretch;
        }

        .order-image-box {
            min-height: 560px;
            overflow: hidden;
            background: #ffffff;
            border: 1px solid #f2dce5;
            border-radius: 22px;
            box-shadow: 0 8px 25px rgba(214, 83, 123, 0.08);
        }

        .order-image-box img {
            width: 100%;
            height: 100%;
            min-height: 560px;
            display: block;
            object-fit: cover;
            object-position: center;
        }

        .order-form-box {
            padding: 32px;
            background: #ffffff;
            border: 1px solid #f2dce5;
            border-radius: 22px;
            box-shadow: 0 8px 25px rgba(214, 83, 123, 0.08);
        }

        .order-form-box h2 {
            margin-bottom: 10px;
            color: #573c49;
            font-size: 25px;
        }

        .order-form-box > p {
            margin-bottom: 25px;
            color: #806b75;
            font-size: 14px;
            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #573c49;
            font-size: 14px;
            font-weight: 700;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #edcbd8;
            border-radius: 10px;
            outline: none;
            color: #573c49;
            background: #ffffff;
            font-family: inherit;
            font-size: 14px;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #d6537b;
            box-shadow: 0 0 0 3px rgba(214, 83, 123, 0.1);
        }

        .submit-button {
            width: 100%;
            padding: 13px 22px;
            border: none;
            border-radius: 25px;
            background: #d6537b;
            color: #ffffff;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s ease;
        }

        .submit-button:hover {
            background: #b83f65;
        }

        .back-home {
            display: block;
            margin-top: 18px;
            text-align: center;
            color: #d6537b;
            font-size: 14px;
            text-decoration: none;
        }

        .back-home:hover {
            text-decoration: underline;
        }

        @media (max-width: 850px) {
            .custom-order-layout {
                grid-template-columns: 1fr;
            }

            .order-image-box {
                min-height: 300px;
                max-height: 400px;
            }

            .order-image-box img {
                min-height: 300px;
                max-height: 400px;
            }
        }

        @media (max-width: 576px) {
            .custom-order-page {
                padding: 30px 15px 45px;
            }

            .page-header h1 {
                font-size: 27px;
            }

            .page-header p {
                font-size: 14px;
            }

            .order-form-box {
                padding: 22px;
            }
        }
    </style>
</head>

<body>

    <main class="custom-order-page">

        <!-- Tiêu đề trang -->
        <div class="page-header">
            <a href="{{ route('home') }}">
                ← Quay lại trang chủ
            </a>

            <h1>Đặt hoa theo yêu cầu</h1>

            <p>
                Hãy chia sẻ mong muốn của bạn để BloomGift tạo nên
                một bó hoa thật đặc biệt và ý nghĩa.
            </p>
        </div>

        <!-- Nội dung chính -->
        <div class="custom-order-layout">

            <!-- Ảnh minh họa -->
            <div class="order-image-box">
                <img
                    src="{{ asset('images/Home/dat-hoa-theo-mong-muon.jpg') }}"
                    alt="Đặt hoa theo mong muốn"
                >
            </div>

            <!-- Form đặt hoa -->
            <div class="order-form-box">
                <h2>Gửi yêu cầu đặt hoa</h2>

                <p>
                    Vui lòng điền thông tin bên dưới.
                    Nhân viên BloomGift sẽ liên hệ với bạn để tư vấn.
                </p>

                <form action="#" method="POST">
                    @csrf

                    <div class="form-group">
                        <label for="name">Họ và tên</label>

                        <input
                            type="text"
                            id="name"
                            name="name"
                            placeholder="Nhập họ và tên"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="phone">Số điện thoại</label>

                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            placeholder="Nhập số điện thoại"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="flower_type">Loại hoa mong muốn</label>

                        <select id="flower_type" name="flower_type" required>
                            <option value="">-- Chọn loại hoa --</option>
                            <option value="Hoa hồng">Hoa hồng</option>
                            <option value="Hoa tulip">Hoa tulip</option>
                            <option value="Hoa cúc">Hoa cúc</option>
                            <option value="Hoa khai trương">Hoa khai trương</option>
                            <option value="Loại hoa khác">Loại hoa khác</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="budget">Ngân sách dự kiến</label>

                        <input
                            type="text"
                            id="budget"
                            name="budget"
                            placeholder="Ví dụ: 300.000 - 500.000 VNĐ"
                        >
                    </div>

                    <div class="form-group">
                        <label for="message">Yêu cầu chi tiết</label>

                        <textarea
                            id="message"
                            name="message"
                            rows="5"
                            placeholder="Màu sắc, kiểu bó hoa, ngày nhận hoa, lời chúc..."
                            required
                        ></textarea>
                    </div>

                    <button type="submit" class="submit-button">
                        Gửi yêu cầu đặt hoa
                    </button>
                </form>

                <a href="{{ route('home') }}" class="back-home">
                    Quay về trang chủ
                </a>
            </div>

        </div>

    </main>

</body>
</html>
