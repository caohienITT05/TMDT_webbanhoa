<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Danh mục hoa - BloomGift</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #fffafa;
            color: #333;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        /* =========================
           HEADER
        ========================= */

        header {
            background: white;
            padding: 18px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 25px;
            border-bottom: 1px solid #f5e1e5;
        }

        .logo {
            font-size: 25px;
            font-weight: bold;
            color: #d85b7a;
            white-space: nowrap;
        }

        nav {
            display: flex;
            align-items: center;
            gap: 22px;
            flex-wrap: wrap;
        }

        nav a {
            font-weight: 700;
            color: #4d3940;
            font-size: 15px;
        }

        nav a:hover {
            color: #d85b7a;
        }

        nav a.active {
            color: #d85b7a;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-button {
            background: #d85b7a;
            color: white;
            padding: 10px 18px;
            border-radius: 25px;
            font-weight: bold;
            white-space: nowrap;
        }

        .header-button:hover {
            background: #b84465;
        }

        /* =========================
           VIDEO BANNER
        ========================= */

        .flower-video-banner {
            position: relative;
            width: 100%;
            height: 420px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fce8ef;
        }

        .flower-banner-video {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }

        .flower-video-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(
                rgba(65, 25, 42, 0.30),
                rgba(65, 25, 42, 0.55)
            );
            z-index: 1;
        }

        .flower-banner-content {
            position: relative;
            z-index: 2;
            width: 90%;
            max-width: 850px;
            text-align: center;
            color: white;
            padding: 20px;
        }

        .flower-banner-content span {
            display: inline-block;
            margin-bottom: 14px;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        .flower-banner-content h1 {
            margin: 0 0 15px;
            font-size: 46px;
            font-weight: 700;
            line-height: 1.3;
            text-shadow: 0 3px 12px rgba(0, 0, 0, 0.3);
        }

        .flower-banner-content p {
            margin: 0;
            font-size: 18px;
            line-height: 1.6;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        .flower-banner-content span,
        .flower-banner-content h1,
        .flower-banner-content p {
            animation: flowerBannerFade 1.2s ease both;
        }

        .flower-banner-content h1 {
            animation-delay: 0.2s;
        }

        .flower-banner-content p {
            animation-delay: 0.4s;
        }

        @keyframes flowerBannerFade {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* =========================
           SECTION
        ========================= */

        .section {
            padding: 50px 7%;
        }

        .section-title {
            text-align: center;
            margin-bottom: 30px;
        }

        .section-title h2 {
            color: #b84465;
            font-size: 30px;
            margin-bottom: 10px;
        }

        .section-title p {
            color: #777;
        }

        /* =========================
           CATEGORY CARDS
        ========================= */

        .categories {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 22px;
        }

        .category-card {
            display: block;
            background: white;
            border: 1px solid #f5e1e5;
            border-radius: 18px;
            padding: 0 0 25px;
            text-align: center;
            transition: 0.3s;
            cursor: pointer;
            overflow: hidden;
        }

        .category-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 6px 20px #eecbd3;
            border-color: #e8a9ba;
        }

        /* Ảnh trong phần Hoa theo dịp */

        .category-image {
            width: 100%;
            height: 410px;
            margin-bottom: 20px;
            overflow: hidden;
            background: #fff0f3;
        }

        .category-image img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
            object-position: center;
            transition: transform 0.4s ease;
        }

        .category-card:hover .category-image img {
            transform: scale(1.08);
        }

        .category-card h3 {
            color: #b84465;
            margin: 0 20px 12px;
            font-size: 20px;
        }

        .category-card p {
            color: #777;
            font-size: 14px;
            line-height: 1.6;
            margin: 0 20px 20px;
            min-height: 67px;
        }

        .btn {
            display: inline-block;
            background: #d85b7a;
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: bold;
        }

        .btn:hover {
            background: #b84465;
        }

        /* =========================
           FOOTER
        ========================= */

        footer {
            background: #b84465;
            color: white;
            padding: 30px 7%;
            text-align: center;
            line-height: 1.8;
            margin-top: 20px;
        }

        footer h3 {
            margin-bottom: 8px;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 1100px) {
            header {
                flex-wrap: wrap;
            }

            .categories {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            header {
                flex-direction: column;
                gap: 15px;
            }

            nav {
                gap: 12px;
                justify-content: center;
            }

            .header-actions {
                justify-content: center;
            }

            .flower-video-banner {
                height: 350px;
            }

            .flower-banner-content h1 {
                font-size: 34px;
            }

            .flower-banner-content p {
                font-size: 16px;
            }

            .flower-banner-content span {
                font-size: 12px;
                letter-spacing: 2px;
            }

            .section {
                padding: 35px 5%;
            }
        }

        @media (max-width: 480px) {
            .categories {
                grid-template-columns: 1fr;
            }

            .flower-video-banner {
                height: 300px;
            }

            .flower-banner-content {
                padding: 15px;
            }

            .flower-banner-content h1 {
                font-size: 28px;
            }

            .flower-banner-content p {
                font-size: 14px;
            }

            .flower-banner-content span {
                font-size: 10px;
                letter-spacing: 1.5px;
            }

            .category-image {
                    width: 75%;
                    height: 150px;
                    margin: 20px auto;
            }

            .category-card p {
                min-height: auto;
            }

            .flower-video-banner {
                height: 300px;
            }
        }
    </style>
</head>

<body>

    <!-- =========================
         HEADER
    ========================= -->

    <header>

        <a href="{{ route('home') }}" class="logo">
            🌷 BloomGift
        </a>

        <nav>

            <a
                href="{{ route('home') }}"
                class="{{ request()->routeIs('home') ? 'active' : '' }}"
            >
                Trang chủ
            </a>

            <a
                href="{{ route('categories') }}"
                class="{{ request()->routeIs('categories') ? 'active' : '' }}"
            >
                Danh mục
            </a>

            <a
                href="{{ route('products') }}"
                class="{{ request()->routeIs('products') ? 'active' : '' }}"
            >
                Sản phẩm
            </a>

            <a
                href="{{ route('favorites.index') }}"
                class="{{ request()->routeIs('favorites.index') ? 'active' : '' }}"
            >
                Yêu thích ♡
            </a>

        </nav>

        <div class="header-actions">

            <a
                href="{{ route('cart.index') }}"
                class="header-button"
            >
                🛒 Giỏ hàng
            </a>

        </div>

    </header>


    <!-- =========================
         VIDEO BANNER
    ========================= -->

    <section class="flower-video-banner">

        <video
            class="flower-banner-video"
            autoplay
            muted
            loop
            playsinline
        >

            <source
                src="{{ asset('images/Categories/banner-hoa.mp4') }}"
                type="video/mp4"
            >

            Trình duyệt của bạn không hỗ trợ video.

        </video>

        <div class="flower-video-overlay"></div>

        <div class="flower-banner-content">

            <span>BLOOMGIFT</span>

            <h1>Gửi trọn yêu thương</h1>

            <p>
                Mỗi đóa hoa là một lời nhắn gửi yêu thương và ý nghĩa
            </p>

        </div>

    </section>


    <!-- =========================
         HOA THEO DỊP
    ========================= -->

    <section class="section">

        <div class="section-title">

            <h2>Hoa theo dịp</h2>

        </div>


        <div class="categories">

            <!-- Hoa sinh nhật -->

            <a
                href="{{ route('products', ['category' => 'hoa-sinh-nhat']) }}"
                class="category-card"
            >

                <div class="category-image">

                    <img
                        src="{{ asset('images/Categories/hoa-sinh-nhat.jpg') }}"
                        alt="Hoa sinh nhật"
                    >

                </div>

                <h3>
                    Hoa sinh nhật
                </h3>

                <p>
                    Những bó hoa tươi đẹp dành tặng bạn bè
                    và người thân trong ngày sinh nhật.
                </p>

                <span class="btn">
                    Xem sản phẩm
                </span>

            </a>


            <!-- Hoa khai trương -->

            <a
                href="{{ route('products', ['category' => 'hoa-khai-truong']) }}"
                class="category-card"
            >

                <div class="category-image">

                    <img
                        src="{{ asset('images/Categories/hoa-khai-truong.jpg') }}"
                        alt="Hoa khai trương"
                    >

                </div>

                <h3>
                    Hoa khai trương
                </h3>

                <p>
                    Những lẵng hoa sang trọng thay lời chúc
                    thành công và phát triển.
                </p>

                <span class="btn">
                    Xem sản phẩm
                </span>

            </a>


            <!-- Hoa tình yêu -->

            <a
                href="{{ route('products', ['category' => 'hoa-tinh-yeu']) }}"
                class="category-card"
            >

                <div class="category-image">

                    <img
                        src="{{ asset('images/Categories/hoa-tinh-yeu.jpg') }}"
                        alt="Hoa tình yêu"
                    >

                </div>

                <h3>
                    Hoa tình yêu
                </h3>

                <p>
                    Trao gửi những cảm xúc ngọt ngào qua
                    những bó hoa đầy yêu thương.
                </p>

                <span class="btn">
                    Xem sản phẩm
                </span>

            </a>


            <!-- Hoa dịp lễ -->

            <a
                href="{{ route('products', ['category' => 'hoa-dip-le']) }}"
                class="category-card"
            >

                <div class="category-image">

                    <img
                        src="{{ asset('images/Categories/hoa-dip-le.jpg') }}"
                        alt="Hoa dịp lễ"
                    >

                </div>

                <h3>
                    Hoa dịp lễ
                </h3>

                <p>
                    Những mẫu hoa đẹp cho các dịp lễ,
                    sự kiện và ngày kỷ niệm.
                </p>

                <span class="btn">
                    Xem sản phẩm
                </span>

            </a>

        </div>

    </section>


    <!-- =========================
         FOOTER
    ========================= -->

    <footer>

        <h3>
            🌷 BloomGift
        </h3>

        <p>
            Trao yêu thương bằng những đóa hoa tươi đẹp.
        </p>

        <p>
            © 2026 BloomGift. All rights reserved.
        </p>

    </footer>

</body>

</html>
