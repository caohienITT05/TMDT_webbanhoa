<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sản phẩm - BloomGift</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #fff8fb;
            color: #3f2934;
        }

        a {
            text-decoration: none;
        }

        /* =========================
           HEADER
        ========================= */

        header {
            position: relative;
            background: #ffffff;
            padding: 20px 8%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 25px;
            border-bottom: 1px solid #f3dce6;
        }

        .logo {
            font-size: 26px;
            font-weight: bold;
            color: #c94f7c;
            white-space: nowrap;
        }

        nav {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);

            display: flex;
            align-items: center;
            justify-content: center;
            gap: 22px;
            flex-wrap: wrap;
        }

        nav a {
            color: #573c49;
            font-weight: bold;
            white-space: nowrap;
        }

        nav a:hover {
            color: #c94f7c;
        }

        nav a.active {
            color: #c94f7c;
        }

        .header-cart {
            margin-left: auto;
            white-space: nowrap;
        }

        .header-cart a {
            display: inline-block;
            background: #c94f7c;
            color: white;
            padding: 11px 20px;
            border-radius: 25px;
            font-weight: bold;
            transition: 0.3s;
        }

        .header-cart a:hover {
            background: #a93661;
        }

        /* =========================
           CONTAINER
        ========================= */

        .container {
            width: 84%;
            max-width: 1250px;
            margin: 35px auto;
        }

        h1 {
            text-align: center;
            color: #b83d6d;
            margin-bottom: 28px;
        }

        /* =========================
           SUCCESS MESSAGE
        ========================= */

        .success {
            background: #d1e7dd;
            color: #0f5132;
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 25px;
            border: 1px solid #badbcc;
        }

        /* =========================
           FILTER
        ========================= */

        .filter-box {
            background: white;
            padding: 20px;
            border-radius: 14px;
            margin-bottom: 30px;
            box-shadow: 0 5px 18px rgba(180, 90, 120, 0.08);
        }

        .filter-box form {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        input,
        select,
        button {
            padding: 12px;
            border: 1px solid #e7c5d4;
            border-radius: 8px;
            font-size: 15px;
        }

        input {
            flex: 1;
            min-width: 220px;
        }

        select {
            min-width: 200px;
            background: white;
        }

        .search-button {
            background: #c94f7c;
            color: white;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }

        .search-button:hover {
            background: #a93661;
        }

        /* =========================
           PRODUCT GRID
        ========================= */

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 24px;
        }

        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 18px rgba(180, 90, 120, 0.12);
            transition: 0.2s;
            border: 1px solid #f7e1e9;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(180, 90, 120, 0.2);
        }

        /* =========================
           PRODUCT IMAGE
        ========================= */

        .product-image {
            height: 220px;
            background: linear-gradient(135deg, #ffe0eb, #fff0f5);
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
            object-position: center;
            transition: transform 0.3s ease;
        }

        .product-card:hover .product-image img {
            transform: scale(1.05);
        }

        .default-image {
            font-size: 65px;
        }

        /* =========================
           PRODUCT INFO
        ========================= */

        .product-info {
            padding: 18px;
        }

        .product-info h3 {
            margin: 8px 0 10px;
            color: #a93661;
            min-height: 45px;
            font-size: 20px;
        }

        .category {
            color: #9b7182;
            font-size: 14px;
            margin: 0;
        }

        .description {
            color: #6d5962;
            line-height: 1.5;
            min-height: 45px;
            margin: 10px 0;
        }

        .price {
            color: #d33c72;
            font-size: 20px;
            font-weight: bold;
            margin: 12px 0 16px;
        }

        /* =========================
           ACTIONS
        ========================= */

        .actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .detail-link {
            display: inline-block;
            padding: 10px 14px;
            background: #f8d5e2;
            color: #9d315b;
            border-radius: 8px;
            font-weight: bold;
        }

        .detail-link:hover {
            background: #efb7ce;
        }

        .favorite-button {
            padding: 10px 14px;
            background: #fff0f5;
            color: #c94f7c;
            border: 1px solid #e5a9be;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
        }

        .favorite-button:hover {
            background: #f8d5e2;
        }

        /* =========================
           EMPTY
        ========================= */

        .empty {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 12px;
            border: 1px solid #f3dce6;
        }

        .empty h2 {
            color: #b83d6d;
        }

        /* =========================
           FOOTER
        ========================= */

        footer {
            background: #b83d6d;
            color: white;
            text-align: center;
            padding: 30px 7%;
            margin-top: 50px;
            line-height: 1.8;
        }

        footer h3 {
            margin-bottom: 8px;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 850px) {

            header {
                position: static;
                flex-direction: column;
                text-align: center;
                padding: 20px 5%;
            }

            nav {
                position: static;
                transform: none;
                justify-content: center;
                gap: 15px;
            }

            .header-cart {
                margin-left: 0;
            }

            .container {
                width: 92%;
            }
        }

        @media (max-width: 500px) {

            .filter-box form {
                flex-direction: column;
            }

            input,
            select,
            .search-button {
                width: 100%;
            }

            .product-grid {
                grid-template-columns: 1fr;
            }

            .product-image {
                height: 240px;
            }

            .actions {
                flex-direction: column;
                align-items: stretch;
            }

            .detail-link,
            .favorite-button {
                text-align: center;
                width: 100%;
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
            🌸 BloomGift
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

        <div class="header-cart">

            <a href="{{ route('cart.index') }}">
                🛒 Giỏ hàng
            </a>

        </div>

    </header>


    <!-- =========================
         MAIN
    ========================= -->

    <main class="container">

        <h1>🌷 Sản phẩm BloomGift</h1>


        <!-- Thông báo -->

        @if (session('success'))

            <div class="success">
                {{ session('success') }}
            </div>

        @endif


        <!-- Bộ lọc sản phẩm -->

        <div class="filter-box">

            <form
                action="{{ route('products') }}"
                method="GET"
            >

                <input
                    type="text"
                    name="search"
                    placeholder="Tìm kiếm sản phẩm..."
                    value="{{ request('search') }}"
                >

                <select name="category">

                    <option value="">
                        -- Tất cả danh mục --
                    </option>

                    @foreach ($categories as $category)

                        <option
                            value="{{ $category->slug }}"
                            {{ request('category') == $category->slug ? 'selected' : '' }}
                        >
                            {{ $category->name }}
                        </option>

                    @endforeach

                </select>

                <button
                    type="submit"
                    class="search-button"
                >
                    🔍 Tìm kiếm
                </button>

            </form>

        </div>


        <!-- Danh sách sản phẩm -->

        @if ($products->count() > 0)

            <div class="product-grid">

                @foreach ($products as $product)

                    <div class="product-card">
<!-- Ảnh sản phẩm -->
<div class="product-image">

    <img
        src="{{ asset('images/Products/' . $product->id . '.jpg') }}"
        alt="{{ $product->name }}"
        onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"
    >

    <div class="default-image" style="display: none;">
        🌸
    </div>

</div>
                        <!-- Thông tin sản phẩm -->

                        <div class="product-info">

                            <p class="category">
                                {{ $product->category->name ?? 'Chưa có danh mục' }}
                            </p>

                            <h3>
                                {{ $product->name }}
                            </h3>

                            <p class="description">
                                {{
                                    $product->short_description
                                    ?? $product->description
                                    ?? 'Sản phẩm hoa tươi BloomGift.'
                                }}
                            </p>

                            <div class="price">
                                {{ number_format($product->price, 0, ',', '.') }} VNĐ
                            </div>

                            <div class="actions">

                                <a
                                    class="detail-link"
                                    href="{{ route('product.detail', $product->id) }}"
                                >
                                    Xem chi tiết
                                </a>

                                <form
                                    action="{{ route('favorites.add', $product->id) }}"
                                    method="POST"
                                >

                                    @csrf

                                    <button
                                        type="submit"
                                        class="favorite-button"
                                    >
                                        ♡ Yêu thích
                                    </button>

                                </form>

                            </div>

                        </div>

                    </div>

                @endforeach

            </div>

        @else

            <div class="empty">

                <h2>
                    Không tìm thấy sản phẩm
                </h2>

                <p>
                    Không tìm thấy sản phẩm phù hợp với thông tin tìm kiếm.
                </p>

                <a
                    href="{{ route('products') }}"
                    class="detail-link"
                >
                    Xem tất cả sản phẩm
                </a>

            </div>

        @endif

    </main>


    <!-- =========================
         FOOTER
    ========================= -->

    <footer>

        <h3>
            🌸 BloomGift
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
