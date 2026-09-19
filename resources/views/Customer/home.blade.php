<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>BloomGift - Hoa tươi trao yêu thương</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #fff9fb;
            color: #49343d;
            line-height: 1.6;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        header {
            background: white;
            padding: 18px 7%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 25px;
            border-bottom: 1px solid #f5dce5;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo {
            color: #d6537b;
            font-size: 26px;
            font-weight: bold;
            white-space: nowrap;
        }

        nav {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 22px;
            flex-wrap: wrap;
        }

        nav a {
            color: #573c49;
            font-weight: bold;
            font-size: 15px;
        }

        nav a:hover,
        nav a.active {
            color: #d6537b;
            font-weight: bold;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .header-button {
            background: #d6537b;
            color: white;
            padding: 10px 17px;
            border-radius: 25px;
            font-weight: bold;
            white-space: nowrap;
        }

        .header-button:hover {
            background: #b83d65;
        }

        .hero {
            min-height: 510px;
            padding: 65px 7%;
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
            align-items: center;
            gap: 45px;
            background:
                radial-gradient(circle at 85% 20%, #ffe0eb 0, transparent 28%),
                linear-gradient(120deg, #fff6f8, #ffe8ef);
        }

        .hero-content small {
            color: #d6537b;
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .hero-content h1 {
            color: #b83d65;
            font-size: 48px;
            line-height: 1.2;
            margin: 18px 0;
        }

        .hero-content p {
            color: #725b65;
            font-size: 18px;
            max-width: 540px;
            margin-bottom: 28px;
        }

        .hero-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .primary-button,
        .secondary-button {
            display: inline-block;
            padding: 13px 23px;
            border-radius: 28px;
            font-weight: bold;
        }

        .primary-button {
            background: #d6537b;
            color: white;
        }

        .primary-button:hover {
            background: #b83d65;
        }

        .secondary-button {
            border: 1px solid #d6537b;
            color: #c0446d;
            background: white;
        }

        .secondary-button:hover {
            background: #ffe1eb;
        }

        .hero-visual {
            min-height: 360px;
            border-radius: 35px;
            background:
                radial-gradient(circle at 25% 25%, #ffffff 0, transparent 18%),
                radial-gradient(circle at 75% 20%, #ffffff 0, transparent 16%),
                linear-gradient(145deg, #ffd2e1, #fff0f5);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(190, 76, 115, 0.14);
        }

        .hero-flower {
            font-size: 150px;
            filter: drop-shadow(0 12px 10px rgba(130, 50, 80, 0.12));
        }

        .flower-label {
            position: absolute;
            background: white;
            color: #b83d65;
            padding: 12px 18px;
            border-radius: 18px;
            font-weight: bold;
            box-shadow: 0 8px 20px rgba(190, 76, 115, 0.12);
        }

        .label-one {
            top: 35px;
            left: 25px;
        }

        .label-two {
            right: 25px;
            bottom: 35px;
        }

        .section {
            padding: 65px 7%;
        }

        .section-heading {
            text-align: center;
            margin-bottom: 35px;
        }

        .section-heading h2 {
            color: #b83d65;
            font-size: 32px;
            margin-bottom: 8px;
        }

        .section-heading p {
            color: #806b73;
        }

        .services {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 22px;
        }

        .service-card {
            background: white;
            padding: 28px 20px;
            text-align: center;
            border-radius: 18px;
            border: 1px solid #f4dce5;
            transition: 0.3s;
        }

        .service-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 8px 22px rgba(190, 76, 115, 0.12);
        }

        .service-icon {
            font-size: 45px;
            margin-bottom: 12px;
        }

        .service-card h3 {
            color: #bd456d;
            font-size: 18px;
            margin-bottom: 8px;
        }

        .service-card p {
            color: #806b73;
            font-size: 14px;
        }

        .featured-section {
            background: #fff1f5;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 22px;
        }

        .product-card {
            background: white;
            border-radius: 18px;
            overflow: hidden;
            border: 1px solid #f4dce5;
            transition: 0.3s;
        }

        .product-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 8px 22px rgba(190, 76, 115, 0.14);
        }

        .product-image {
            height: 190px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #ffe0eb, #fff4f7);
            font-size: 85px;
        }

        .product-info {
            padding: 20px;
        }

        .product-info h3 {
            color: #b83d65;
            font-size: 18px;
            margin-bottom: 8px;
        }

        .product-info p {
            color: #806b73;
            font-size: 14px;
            min-height: 45px;
        }

        .product-price {
            color: #d6537b;
            font-size: 20px;
            font-weight: bold;
            margin: 12px 0;
        }

        .product-button {
            display: inline-block;
            background: #f8d5e2;
            color: #a93661;
            padding: 9px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
        }

        .product-button:hover {
            background: #efb7ce;
        }

        .custom-order {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 35px;
            align-items: center;
            background: white;
            border-radius: 25px;
            padding: 40px;
            border: 1px solid #f3dce5;
            box-shadow: 0 8px 25px rgba(190, 76, 115, 0.08);
        }

        .custom-order h2 {
            color: #b83d65;
            font-size: 30px;
            margin-bottom: 15px;
        }

        .custom-order p {
            color: #806b73;
            margin-bottom: 20px;
        }

        .custom-list {
            list-style: none;
            margin-bottom: 25px;
        }

        .custom-list li {
            margin: 12px 0;
            color: #5e4650;
        }

        .custom-visual {
            min-height: 280px;
            border-radius: 22px;
            background: linear-gradient(135deg, #ffe0eb, #fff4f7);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 115px;
        }

        .steps {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 22px;
        }

        .step-card {
            text-align: center;
            padding: 25px 18px;
        }

        .step-number {
            width: 58px;
            height: 58px;
            margin: 0 auto 15px;
            border-radius: 50%;
            background: #d6537b;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
        }

        .step-card h3 {
            color: #b83d65;
            margin-bottom: 8px;
        }

        .step-card p {
            color: #806b73;
            font-size: 14px;
        }

        .reviews {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 22px;
        }

        .review-card {
            background: white;
            padding: 28px;
            border-radius: 18px;
            border: 1px solid #f4dce5;
        }

        .stars {
            color: #f0a52b;
            font-size: 20px;
            margin-bottom: 12px;
        }

        .review-card p {
            color: #705b64;
            font-style: italic;
            margin-bottom: 15px;
        }

        .review-card strong {
            color: #b83d65;
        }

        footer {
            background: #b83d65;
            color: white;
            padding: 40px 7%;
        }

        .footer-content {
            display: grid;
            grid-template-columns: 1.3fr 1fr 1fr;
            gap: 35px;
        }

        footer h3 {
            margin-bottom: 12px;
            font-size: 22px;
        }

        footer p {
            color: #ffe8ef;
            margin-bottom: 8px;
        }

        footer a {
            display: block;
            color: #ffe8ef;
            margin: 8px 0;
        }

        footer a:hover {
            color: white;
        }

        .copyright {
            text-align: center;
            border-top: 1px solid rgba(255, 255, 255, 0.25);
            padding-top: 20px;
            margin-top: 30px;
            color: #ffe8ef;
        }

        @media (max-width: 1100px) {
            header {
                flex-wrap: wrap;
                justify-content: center;
            }

            .services,
            .product-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .steps {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .hero {
                grid-template-columns: 1fr;
                padding: 45px 6%;
            }

            .hero-content {
                text-align: center;
            }

            .hero-content h1 {
                font-size: 36px;
            }

            .hero-buttons {
                justify-content: center;
            }

            .custom-order {
                grid-template-columns: 1fr;
                padding: 28px;
            }

            .reviews,
            .footer-content {
                grid-template-columns: 1fr;
            }

            .section {
                padding: 45px 6%;
            }

            nav {
                gap: 12px;
            }
        }

        @media (max-width: 480px) {

            .services,
            .product-grid,
            .steps {
                grid-template-columns: 1fr;
            }

            .hero-content h1 {
                font-size: 30px;
            }

            .hero-visual {
                min-height: 260px;
            }

            .hero-flower {
                font-size: 110px;
            }

            .section-heading h2 {
                font-size: 27px;
            }
        }
    </style>
</head>

<body>

    <!-- HEADER -->
    <header>
        <div class="user-auth-nav d-flex align-items-center gap-3">
            @guest
                <!-- 1. DÀNH CHO KHÁCH VÃNG LAI (Chưa đăng nhập) -->
                <a href="{{ route('login') }}" class="btn btn-outline-primary btn-sm">Đăng nhập</a>
                <a href="{{ route('register') }}" class="btn btn-primary btn-sm">Đăng ký</a>
            @else
                <!-- 2. DÀNH CHO TÀI KHOẢN ĐÃ ĐĂNG NHẬP -->
                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle d-flex align-items-center gap-2" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle"></i>
                        <span>{{ Auth::user()->name }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        @if(Auth::user()->role === 'admin')
                            <!-- Dành riêng cho Admin -->
                            <li>
                                <a class="dropdown-item text-danger font-weight-bold" href="{{ route('admin.dashboard') }}">
                                    <i class="bi bi-speedometer2 me-2"></i>Trang Quản Trị
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                        @endif

                        <!-- Dành cho Khách hàng thường -->
                        <li>
                            <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                <i class="bi bi-gear me-2"></i>Tài khoản của tôi
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right me-2"></i>Đăng xuất
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            @endguest
        </div>
        <a href="{{ route('home') }}" class="logo">
            🌷 BloomGift
        </a>

        <nav>
            <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">
                Trang chủ
            </a>

            <a href="{{ route('categories') }}" class="{{ request()->routeIs('categories') ? 'active' : '' }}">
                Danh mục
            </a>

            <a href="{{ route('products') }}" class="{{ request()->routeIs('products') ? 'active' : '' }}">
                Sản phẩm
            </a>

            <a href="{{ route('profile.edit') }}" class="{{ request()->routeIs('profile.edit') ? 'active' : '' }}">
                Yêu thích ♡
            </a>
        </nav>

        <div class="header-actions">

            <a href="{{ route('cart.index') }}" class="header-button">
                🛒 Giỏ hàng
            </a>
        </div>
    </header>

    <!-- HERO -->
    <section class="hero">
        <div class="hero-content">
            <small>CHÀO MỪNG ĐẾN VỚI BLOOMGIFT</small>

            <h1>
                Hoa tươi cho<br>
                mọi khoảnh khắc
            </h1>

            <p>
                Gửi trao yêu thương bằng những bó hoa tươi đẹp,
                được lựa chọn và thiết kế dành riêng cho người bạn yêu quý.
            </p>

            <div class="hero-buttons">
                <a href="{{ route('products') }}" class="primary-button">
                    🌸 Khám phá sản phẩm
                </a>

                <a href="{{ route('categories') }}" class="secondary-button">
                    Xem danh mục
                </a>
            </div>
        </div>

        <div class="hero-visual">
            <div class="flower-label label-one">
                🌷 Hoa tươi mỗi ngày
            </div>

            <div class="hero-flower">
                <img src="{{ asset('images/Home/tiemhoa1.png') }}" alt="Banner cửa hàng hoa" style="
        width: 100%;
        height: 100%;
        display: block;
        object-fit: cover;
        object-position: center;
        border-radius: 30px;
    ">
            </div>

            <div class="flower-label label-two">
                💌 Trao gửi yêu thương
            </div>
        </div>
    </section>

    <!-- SERVICES -->
    <section class="section services-section">
        <div class="section-heading">
            <h2>Dịch vụ nổi bật</h2>
            <p>BloomGift mang đến trải nghiệm đặt hoa đơn giản và tiện lợi</p>
        </div>

        <div class="services">

            <!-- Dịch vụ 1 -->
            <div class="service-card">
                <img src="{{ asset('images/Home/giao-hang.jpg') }}" alt="Giao hàng đúng hẹn" class="service-image">

                <h3>Giao hàng đúng hẹn</h3>

                <p>
                    Lựa chọn ngày và khung giờ giao hoa phù hợp.
                </p>
            </div>

            <!-- Dịch vụ 2 -->
            <div class="service-card">
                <img src="{{ asset('images/Home/hoa-tuoi.jpg') }}" alt="Hoa tươi mỗi ngày" class="service-image">

                <h3>Hoa tươi mỗi ngày</h3>

                <p>
                    Những mẫu hoa đẹp, phù hợp với nhiều dịp đặc biệt.
                </p>
            </div>

            <!-- Dịch vụ 3 -->
            <div class="service-card">
                <img src="{{ asset('images/Home/goi-qua.jpg') }}" alt="Gói quà theo yêu cầu" class="service-image">

                <h3>Gói quà theo yêu cầu</h3>

                <p>
                    Tùy chọn gói quà để món quà trở nên ý nghĩa hơn.
                </p>
            </div>

            <!-- Dịch vụ 4 -->
            <div class="service-card">
                <img src="{{ asset('images/Home/thiep-loi-chuc.jpg') }}" alt="Thiệp và lời chúc" class="service-image">

                <h3>Thiệp và lời chúc</h3>

                <p>
                    Thêm lời nhắn riêng để gửi gắm tình cảm của bạn.
                </p>
            </div>

        </div>
    </section>

    <style>
        /* ==============================
       DỊCH VỤ NỔI BẬT
    ============================== */

        .services-section {
            background: #fff9fb;
        }

        .services {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 22px;
        }

        .service-card {
            background: white;
            padding: 20px;
            text-align: center;
            border-radius: 18px;
            border: 1px solid #f4dce5;
            transition: 0.3s;
            overflow: hidden;
        }

        .service-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 8px 22px rgba(190, 76, 115, 0.12);
        }

        .service-image {
            width: 100%;
            height: 270px;
            padding: 1px;
            object-fit: cover;
            display: block;
            border-radius: 14px;
            margin-bottom: 18px;
        }

        .service-card h3 {
            color: #bd456d;
            font-size: 18px;
            margin-bottom: 8px;
        }

        .service-card p {
            color: #806b73;
            font-size: 14px;
            line-height: 1.6;
        }

        /* Máy tính bảng */
        @media (max-width: 1100px) {
            .services {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Điện thoại */
        @media (max-width: 480px) {
            .services {
                grid-template-columns: 1fr;
            }

            .service-image {
                height: 200px;
            }
        }
    </style>

    <!-- FEATURED PRODUCTS -->
    <section class="section featured-section">
        <div class="section-heading">
            <h2>Sản phẩm nổi bật</h2>
            <p>Những mẫu hoa được nhiều khách hàng yêu thích</p>
        </div>

        <div class="product-grid">

            <!-- Sản phẩm 1 -->
            <div class="product-card">
                <div class="product-image">
                    <img src="{{ asset('images/Home/hoa-hong-do.jpg') }}" alt="Bó Hoa Hồng Đỏ Tình Yêu">
                </div>

                <div class="product-info">
                    <h3>Bó Hoa Hồng Đỏ Tình Yêu</h3>

                    <p>
                        Bó hoa hồng đỏ thể hiện tình yêu và sự quan tâm.
                    </p>

                    <div class="product-price">
                        350.000 VNĐ
                    </div>

                    <a href="{{ route('products') }}" class="product-button">
                        Xem sản phẩm
                    </a>
                </div>
            </div>

            <!-- Sản phẩm 2 -->
            <div class="product-card">
                <div class="product-image">
                    <img src="{{ asset('images/Home/hoa-tulip-pastel.jpg') }}" alt="Bó Hoa Tulip Pastel">
                </div>

                <div class="product-info">
                    <h3>Bó Hoa Tulip Pastel</h3>

                    <p>
                        Mẫu hoa nhẹ nhàng, phù hợp làm quà sinh nhật.
                    </p>

                    <div class="product-price">
                        450.000 VNĐ
                    </div>

                    <a href="{{ route('products') }}" class="product-button">
                        Xem sản phẩm
                    </a>
                </div>
            </div>

            <!-- Sản phẩm 3 -->
            <div class="product-card">
                <div class="product-image">
                    <img src="{{ asset('images/Home/gio-hoa-khai-truong.jpg') }}" alt="Giỏ Hoa Khai Trương">
                </div>

                <div class="product-info">
                    <h3>Giỏ Hoa Khai Trương</h3>

                    <p>
                        Lẵng hoa sang trọng thay lời chúc thành công.
                    </p>

                    <div class="product-price">
                        650.000 VNĐ
                    </div>

                    <a href="{{ route('products') }}" class="product-button">
                        Xem sản phẩm
                    </a>
                </div>
            </div>

            <!-- Sản phẩm 4 -->
            <div class="product-card">
                <div class="product-image">
                    <img src="{{ asset('images/Home/hoa-cuc-hoa-mi.jpg') }}" alt="Bó Hoa Cúc Họa Mi">
                </div>

                <div class="product-info">
                    <h3>Bó Hoa Cúc Họa Mi</h3>

                    <p>
                        Vẻ đẹp trong trẻo, phù hợp tặng người thân.
                    </p>

                    <div class="product-price">
                        300.000 VNĐ
                    </div>

                    <a href="{{ route('products') }}" class="product-button">
                        Xem sản phẩm
                    </a>
                </div>
            </div>

        </div>
    </section>


    <!-- CSS CHO PHẦN SẢN PHẨM NỔI BẬT -->
    <style>
        .featured-section {
            padding: 60px 0;
            background: #fffafd;
        }

        .section-heading {
            text-align: center;
            margin-bottom: 35px;
        }

        .section-heading h2 {
            margin-bottom: 10px;
            color: #573c49;
            font-size: 32px;
            font-weight: 700;
        }

        .section-heading p {
            color: #8b6f7b;
            font-size: 16px;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
        }

        .product-card {
            overflow: hidden;
            background: #ffffff;
            border: 1px solid #f2dce5;
            border-radius: 18px;
            box-shadow: 0 5px 18px rgba(214, 83, 123, 0.08);
            transition: all 0.3s ease;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 28px rgba(214, 83, 123, 0.18);
        }

        .product-image {
            width: 100%;
            height: 250px;
            overflow: hidden;
            background: #fff1f6;
        }

        .product-image {
            width: 100%;
            height: 350px;
            overflow: hidden;
            background: #fff1f6;
            border-radius: 18px 18px 0 0;
            box-sizing: border-box;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: contain;
            object-position: center;
            transition: transform 0.4s ease;
        }

        .product-card:hover .product-image img {
            transform: scale(1.03);
        }

        .product-card:hover .product-image img {
            transform: scale(1.08);
        }

        .product-info {
            padding: 15px 18px 20px;
            text-align: center;
        }

        .product-info h3 {
            margin: 0 0 8px;
            color: #573c49;
            font-size: 18px;
            line-height: 1.35;
        }

        .product-info p {
            margin: 0 0 10px;
            color: #806b75;
            font-size: 14px;
            line-height: 1.5;
        }

        .product-price {
            margin: 0 0 12px;
            color: #d6537b;
            font-size: 19px;
            font-weight: 700;
        }

        .product-button {
            display: inline-block;
            padding: 9px 20px;
            border-radius: 25px;
            background: #d6537b;
            color: #ffffff;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .product-info h3 {
            min-height: 48px;
            margin-bottom: 1px;
            color: #573c49;
            font-size: 19px;
            line-height: 1.4;
        }

        .product-info p {
            min-height: 66px;
            margin-bottom: 2px;
            color: #806b75;
            font-size: 14px;
            line-height: 1.6;
        }

        .product-price {
            margin-bottom: 4px;
            color: #d6537b;
            font-size: 20px;
            font-weight: 700;
        }

        .product-button {
            display: inline-block;
            padding: 11px 22px;
            border-radius: 25px;
            background: #d6537b;
            color: #ffffff;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .product-button:hover {
            background: #b83f65;
            color: #ffffff;
            transform: translateY(-2px);
        }

        /* Tablet */
        @media (max-width: 992px) {
            .product-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Điện thoại */
        @media (max-width: 576px) {
            .featured-section {
                padding: 40px 15px;
            }

            .section-heading h2 {
                font-size: 26px;
            }

            .product-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .product-image {
                height: 280px;
            }
        }
    </style>
    <!-- CUSTOM FLOWER ORDER -->
    <section class="section custom-order-section">
        <div class="custom-order-container">

            <!-- Hình ảnh -->
            <div class="custom-order-image">
                <img src="{{ asset('images/Home/dat-hoa-theo-mong-muon.jpg') }}" alt="Đặt hoa theo mong muốn">
            </div>

            <!-- Nội dung -->
            <div class="custom-order-content">
                <span class="custom-order-label">
                    BLOOMGIFT FLOWER
                </span>

                <h2>Đặt hoa theo mong muốn</h2>

                <p>
                    Bạn muốn một bó hoa mang dấu ấn riêng?
                    Hãy chia sẻ ý tưởng của bạn, BloomGift sẽ giúp
                    bạn tạo nên một sản phẩm hoa thật đặc biệt.
                </p>

                <ul class="custom-order-list">
                    <li>🌸 Tự chọn loại hoa yêu thích</li>
                    <li>🎨 Lựa chọn màu sắc và kiểu bó hoa</li>
                    <li>🎁 Thiết kế hoa theo từng dịp đặc biệt</li>
                    <li>💌 Gửi lời chúc riêng đến người nhận</li>
                </ul>

                <a href="{{ route('custom.order') }}" class="custom-order-button">
                    Đặt hoa ngay
                </a>
            </div>

        </div>
    </section>


    <!-- CSS CHO PHẦN ĐẶT HOA THEO MONG MUỐN -->
    <style>
        .custom-order-section {
            padding: 65px 0;
            background: #fff7fa;
        }

        .custom-order-container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            align-items: center;
            gap: 45px;
            padding: 30px;
            background: #ffffff;
            border: 1px solid #f3dce5;
            border-radius: 25px;
            box-shadow: 0 8px 25px rgba(214, 83, 123, 0.08);
            box-sizing: border-box;
        }

        .custom-order-image {
            width: 100%;
            height: 390px;
            overflow: hidden;
            border-radius: 20px;
            background: #fff0f5;
        }

        .custom-order-image img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
            object-position: center;
            transition: transform 0.4s ease;
        }

        .custom-order-image:hover img {
            transform: scale(1.04);
        }

        .custom-order-content {
            padding: 10px 15px;
        }

        .custom-order-label {
            display: inline-block;
            margin-bottom: 12px;
            color: #d6537b;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 1.5px;
        }

        .custom-order-content h2 {
            margin: 0 0 15px;
            color: #573c49;
            font-size: 32px;
            line-height: 1.3;
        }

        .custom-order-content p {
            margin: 0 0 18px;
            color: #806b75;
            font-size: 16px;
            line-height: 1.7;
        }

        .custom-order-list {
            margin: 0 0 25px;
            padding: 0;
            list-style: none;
        }

        .custom-order-list li {
            margin-bottom: 12px;
            color: #654d59;
            font-size: 15px;
            line-height: 1.5;
        }

        .custom-order-button {
            display: inline-block;
            padding: 12px 26px;
            border-radius: 25px;
            background: #d6537b;
            color: #ffffff;
            font-size: 15px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .custom-order-button:hover {
            background: #b83f65;
            color: #ffffff;
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(214, 83, 123, 0.2);
        }

        /* Tablet */
        @media (max-width: 992px) {
            .custom-order-container {
                grid-template-columns: 1fr;
                gap: 25px;
                padding: 25px;
            }

            .custom-order-image {
                height: 330px;
            }

            .custom-order-content {
                padding: 5px;
            }
        }

        /* Điện thoại */
        @media (max-width: 576px) {
            .custom-order-section {
                padding: 40px 15px;
            }

            .custom-order-container {
                width: 100%;
                padding: 18px;
                border-radius: 18px;
            }

            .custom-order-image {
                height: 260px;
            }

            .custom-order-content h2 {
                font-size: 26px;
            }

            .custom-order-content p {
                font-size: 14px;
            }

            .custom-order-list li {
                font-size: 14px;
            }
        }
    </style>

    <!-- ORDER PROCESS -->
    <section class="section process-section">
        <div class="section-heading">
            <h2>Quy trình đặt hoa</h2>
            <p>Đặt hoa đơn giản, nhanh chóng cùng BloomGift</p>
        </div>

        <div class="process-grid">

            <!-- Bước 1 -->
            <div class="process-card">
                <div class="process-image">
                    <img src="{{ asset('images/Home/chon-hoa.jpg') }}" alt="Chọn mẫu hoa">
                </div>

                <div class="process-number">01</div>

                <div class="process-content">
                    <h3>Chọn mẫu hoa</h3>
                    <p>
                        Lựa chọn mẫu hoa phù hợp với sở thích
                        và dịp tặng của bạn.
                    </p>
                </div>
            </div>

            <!-- Bước 2 -->
            <div class="process-card">
                <div class="process-image">
                    <img src="{{ asset('images/Home/tu-van-hoa.jpg') }}" alt="Tư vấn và thiết kế hoa">
                </div>

                <div class="process-number">02</div>

                <div class="process-content">
                    <h3>Tư vấn và thiết kế</h3>
                    <p>
                        Nhân viên tư vấn loại hoa, màu sắc
                        và cách bó theo mong muốn.
                    </p>
                </div>
            </div>

            <!-- Bước 3 -->
            <div class="process-card">
                <div class="process-image">
                    <img src="{{ asset('images/Home/xac-nhan-don.jpg') }}" alt="Xác nhận đơn hàng">
                </div>

                <div class="process-number">03</div>

                <div class="process-content">
                    <h3>Xác nhận đơn hàng</h3>
                    <p>
                        Kiểm tra thông tin, giá sản phẩm,
                        thời gian và địa chỉ nhận hoa.
                    </p>
                </div>
            </div>

            <!-- Bước 4 -->
            <div class="process-card">
                <div class="process-image">
                    <img src="{{ asset('images/Home/giao-hoa.jpg') }}" alt="Giao hoa tận nơi">
                </div>

                <div class="process-number">04</div>

                <div class="process-content">
                    <h3>Giao hoa tận nơi</h3>
                    <p>
                        Hoa được chuẩn bị cẩn thận và giao
                        đến tận tay người nhận.
                    </p>
                </div>
            </div>

        </div>
    </section>


    <!-- CSS CHO PHẦN QUY TRÌNH ĐẶT HOA -->
    <style>
        .process-section {
            padding: 65px 0;
            background: #ffffff;
        }

        .process-grid {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
        }

        .process-card {
            position: relative;
            overflow: hidden;
            background: #ffffff;
            border: 1px solid #f2dce5;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 6px 20px rgba(214, 83, 123, 0.08);
            transition: all 0.3s ease;
        }

        .process-card:hover {
            transform: translateY(-7px);
            box-shadow: 0 12px 28px rgba(214, 83, 123, 0.16);
        }

        .process-image {
            width: 100%;
            height: 190px;
            overflow: hidden;
            background: #fff1f6;
        }

        .process-image img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
            object-position: center;
            transition: transform 0.4s ease;
        }

        .process-card:hover .process-image img {
            transform: scale(1.06);
        }

        .process-number {
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: -22px auto 12px;
            position: relative;
            border-radius: 50%;
            background: #d6537b;
            color: #ffffff;
            font-size: 16px;
            font-weight: 700;
            border: 4px solid #ffffff;
        }

        .process-content {
            padding: 0 20px 25px;
        }

        .process-content h3 {
            margin: 0 0 10px;
            color: #573c49;
            font-size: 19px;
            line-height: 1.4;
        }

        .process-content p {
            margin: 0;
            color: #806b75;
            font-size: 14px;
            line-height: 1.6;
        }

        /* Tablet */
        @media (max-width: 992px) {
            .process-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .process-image {
                height: 220px;
            }
        }

        /* Điện thoại */
        @media (max-width: 576px) {
            .process-section {
                padding: 40px 15px;
            }

            .process-grid {
                width: 100%;
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .process-image {
                height: 240px;
            }
        }
    </style>

    <!-- REVIEWS -->
    <section class="section">
        <div class="section-heading">
            <h2>Khách hàng nói gì?</h2>
            <p>Những lời yêu thương dành cho BloomGift</p>
        </div>

        <div class="reviews">

            <div class="review-card">
                <div class="stars">★★★★★</div>

                <p>
                    “Hoa rất đẹp, màu sắc tươi và được gói rất cẩn thận.
                    Mình sẽ tiếp tục ủng hộ shop.”
                </p>

                <strong>Nguyễn Minh Anh</strong>
            </div>

            <div class="review-card">
                <div class="stars">★★★★★</div>

                <p>
                    “Shop tư vấn nhiệt tình, giao hoa đúng giờ.
                    Người nhận rất vui và bất ngờ.”
                </p>

                <strong>Trần Thu Hà</strong>
            </div>

            <div class="review-card">
                <div class="stars">★★★★★</div>

                <p>
                    “Gói quà đẹp, lời chúc được chuẩn bị chỉn chu.
                    Trải nghiệm đặt hoa rất thuận tiện.”
                </p>

                <strong>Lê Hoàng Nam</strong>
            </div>

        </div>
    </section>

    <!-- FOOTER -->
    <!-- KHỐI CHÍNH SÁCH PHÁP LÝ TMĐT BLOOMGIFT -->
    <footer class="bg-white border-top py-5 mt-5">
        <div class="container">
            <div class="row g-4">
                <!-- Cột 1: Thông tin tiệm hoa -->
                <div class="col-lg-4 col-md-6">
                    <h5 class="fw-bold text-danger mb-3">🌸 BloomGift - Hoa Tươi & Quà Tặng</h5>
                    <p class="text-muted small">Trao gửi yêu thương qua từng đóa hoa tươi thắm. Dịch vụ đặt hoa trực
                        tuyến giao đúng hẹn theo khung giờ, thiệp chúc miễn phí.</p>
                    <p class="text-muted small mb-1"><i class="bi bi-geo-alt me-2 text-danger"></i>Hà Đông, Hà Nội, Việt
                        Nam</p>
                    <p class="text-muted small mb-1"><i class="bi bi-telephone me-2 text-danger"></i>Hotline:
                        0988.123.456</p>
                    <p class="text-muted small"><i class="bi bi-envelope me-2 text-danger"></i>Email:
                        support@bloomgift.local</p>
                </div>

                <!-- Cột 2: Quy định & Chính sách TMĐT (Bắt buộc) -->
                <div class="col-lg-4 col-md-6">
                    <h5 class="fw-bold text-secondary mb-3">Chính Sách & Quy Định</h5>
                    <ul class="list-unstyled small">
                        <li class="mb-2">
                            <a href="{{ route('policies.terms') }}" class="text-decoration-none text-muted">
                                <i class="bi bi-chevron-right text-danger me-1"></i>Điều kiện giao dịch chung & Người
                                bán
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="{{ route('policies.returns') }}" class="text-decoration-none text-muted">
                                <i class="bi bi-chevron-right text-danger me-1"></i>Chính sách đổi trả & Hoàn tiền
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="{{ route('policies.privacy') }}" class="text-decoration-none text-muted">
                                <i class="bi bi-chevron-right text-danger me-1"></i>Chính sách bảo vệ dữ liệu cá nhân
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Cột 3: Phương thức thanh toán chấp nhận -->
                <div class="col-lg-4 col-md-12">
                    <h5 class="fw-bold text-secondary mb-3">Thanh Toán An Toàn</h5>
                    <p class="text-muted small">Hỗ trợ thanh toán tiền mặt khi nhận hoa (COD) và thanh toán trực tuyến
                        thử nghiệm qua PayPal Sandbox.</p>
                    <div class="d-flex gap-2 align-items-center mt-2">
                        <span class="badge bg-light text-dark border p-2"><i class="bi bi-cash me-1 text-success"></i>
                            COD</span>
                        <span class="badge bg-light text-dark border p-2"><i class="bi bi-paypal me-1 text-primary"></i>
                            PayPal Sandbox</span>
                    </div>
                </div>
            </div>

            <hr class="my-4 text-muted">
            <div class="text-center small text-muted">
                © 2026 BloomGift Shop. Hệ thống Thương mại Điện tử Hoa tươi tuân thủ quy định pháp luật Việt Nam.
            </div>
        </div>
    </footer>

</body>

</html>