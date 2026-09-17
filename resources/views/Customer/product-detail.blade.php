
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $product->name }} - BloomGift</title>

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

        .container {
            width: 84%;
            max-width: 1200px;
            margin: 45px auto;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 25px;
            color: #b83d6d;
            text-decoration: none;
            font-weight: bold;
        }

        .back-link:hover {
            color: #8f284f;
        }

        .success {
            background: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 25px;
        }

        .product-detail {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            background: white;
            padding: 35px;
            border-radius: 18px;
            box-shadow: 0 8px 25px rgba(180, 90, 120, 0.12);
        }

        .product-image {
            min-height: 350px;
            background: linear-gradient(135deg, #ffe0eb, #fff0f5);
            border-radius: 15px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 120px;
        }

        .category {
            color: #a8758a;
            font-size: 15px;
            margin-bottom: 12px;
        }

        h1 {
            color: #b83d6d;
            font-size: 32px;
            line-height: 1.3;
            margin-top: 0;
        }

        .price {
            color: #d33c72;
            font-size: 28px;
            font-weight: bold;
            margin: 25px 0;
        }

        .description-title {
            color: #a93661;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .description {
            line-height: 1.8;
            font-size: 17px;
            color: #5d4a53;
        }

        .stock {
            margin: 20px 0;
            color: #557b55;
            font-weight: bold;
        }

        .stock-out {
            color: #dc3545;
        }

        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 25px;
        }

        .buy-button,
        .favorite-button {
            display: inline-block;
            padding: 14px 20px;
            color: white;
            border-radius: 9px;
            font-weight: bold;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }

        .buy-button {
            background: #c94f7c;
        }

        .buy-button:hover {
            background: #a93661;
        }

        .favorite-button {
            background: #fff0f5;
            color: #c94f7c;
            border: 1px solid #d88aa7;
        }

        .favorite-button:hover {
            background: #f8d5e2;
        }

        .remove-button {
            background: #dc3545;
            color: white;
            border: none;
            padding: 14px 20px;
            border-radius: 9px;
            font-weight: bold;
            cursor: pointer;
            font-size: 16px;
        }

        .remove-button:hover {
            background: #b02a37;
        }

        .disabled-button {
            background: #adb5bd;
            color: white;
            border: none;
            padding: 14px 20px;
            border-radius: 9px;
            font-weight: bold;
            font-size: 16px;
            cursor: not-allowed;
        }

        @media (max-width: 850px) {
            header {
                flex-direction: column;
                gap: 15px;
            }

            nav {
                justify-content: center;
            }

            .product-detail {
                grid-template-columns: 1fr;
                padding: 25px;
            }

            .container {
                width: 92%;
            }

            .product-image {
                min-height: 260px;
                font-size: 90px;
            }
        }

        @media (max-width: 500px) {
            nav {
                gap: 12px;
            }

            nav a {
                font-size: 14px;
            }

            h1 {
                font-size: 26px;
            }

            .price {
                font-size: 24px;
            }

            .actions form,
            .actions button {
                width: 100%;
            }
        }
    </style>
</head>

<body>

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
    <main class="container">

        @if (session('success'))
            <div class="success">
                {{ session('success') }}
            </div>
        @endif

        <a
            class="back-link"
            href="{{ route('products') }}"
        >
            ← Quay lại danh sách sản phẩm
        </a>

        <div class="product-detail">

            <div class="product-image">
                🌸
            </div>

            <div>

                <div class="category">
                    Danh mục:
                    {{ $product->category->name ?? 'Chưa có danh mục' }}
                </div>

                <h1>
                    {{ $product->name }}
                </h1>

                <div class="price">
                    {{ number_format($product->price, 0, ',', '.') }} VNĐ
                </div>

                <div class="description-title">
                    Mô tả sản phẩm
                </div>

                <p class="description">
                    {{ $product->description ?? 'Sản phẩm hoa tươi chất lượng tại BloomGift.' }}
                </p>

                @if ($product->stock > 0)
                    <div class="stock">
                        ✓ Còn {{ $product->stock }} sản phẩm trong kho
                    </div>
                @else
                    <div class="stock stock-out">
                        ✕ Sản phẩm hiện đã hết hàng
                    </div>
                @endif

                <div class="actions">

                    @if ($product->stock > 0)

                        <form
                            action="{{ route('cart.add', $product->id) }}"
                            method="POST"
                        >
                            @csrf

                            <button
                                class="buy-button"
                                type="submit"
                            >
                                🛒 Thêm vào giỏ hàng
                            </button>
                        </form>

                    @else

                        <button
                            class="disabled-button"
                            type="button"
                            disabled
                        >
                            Hết hàng
                        </button>

                    @endif

                    @php
                        $favoriteIds = session('favorites', []);
                        $isFavorite = in_array($product->id, $favoriteIds);
                    @endphp

                    @if ($isFavorite)

                        <form
                            action="{{ route('favorites.remove', $product->id) }}"
                            method="POST"
                        >
                            @csrf
                            @method('DELETE')

                            <button
                                class="remove-button"
                                type="submit"
                            >
                                ♥ Bỏ yêu thích
                            </button>
                        </form>

                    @else

                        <form
                            action="{{ route('favorites.add', $product->id) }}"
                            method="POST"
                        >
                            @csrf

                            <button
                                class="favorite-button"
                                type="submit"
                            >
                                ♡ Thêm vào yêu thích
                            </button>
                        </form>

                    @endif

                </div>

            </div>
        </div>

    </main>

</body>
</html>
