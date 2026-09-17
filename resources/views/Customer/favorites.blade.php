
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sản phẩm yêu thích - BloomGift</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #fffafa;
            color: #333;
        }

        header {
            background: white;
            padding: 18px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            border-bottom: 1px solid #f5e1e5;
        }

        .logo {
            color: #d85b7a;
            font-size: 25px;
            font-weight: bold;
            text-decoration: none;
        }

        nav {
            display: flex;
            gap: 22px;
            flex-wrap: wrap;
        }

        nav a {
            color: #555;
            text-decoration: none;
        }

        nav a:hover {
            color: #d85b7a;
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 25px;
        }

        h1 {
            color: #b84465;
            text-align: center;
            margin-bottom: 30px;
        }

        .success {
            background: #d1e7dd;
            color: #0f5132;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .products {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 22px;
        }

        .product-card {
            background: white;
            border: 1px solid #f5e1e5;
            border-radius: 15px;
            overflow: hidden;
            transition: 0.3s;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px #eecbd3;
        }

        .product-image {
            height: 180px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 75px;
            background: #fff0f3;
        }

        .product-info {
            padding: 18px;
        }

        .product-info h3 {
            margin: 0 0 10px;
            color: #555;
        }

        .category {
            color: #777;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .price {
            color: #d85b7a;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            padding: 10px 13px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            cursor: pointer;
            color: white;
            font-size: 14px;
        }

        .btn-detail {
            background: #0d6efd;
        }

        .btn-remove {
            background: #dc3545;
        }

        .empty {
            background: white;
            padding: 40px;
            border-radius: 15px;
            text-align: center;
            border: 1px solid #f5e1e5;
        }

        .empty a {
            display: inline-block;
            margin-top: 20px;
            background: #d85b7a;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
        }

        @media (max-width: 900px) {
            .products {
                grid-template-columns: repeat(2, 1fr);
            }

            header {
                flex-direction: column;
            }
        }

        @media (max-width: 500px) {
            .products {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<header>
    <a href="{{ route('home') }}" class="logo">
        🌷 BloomGift
    </a>

    <nav>
        <a href="{{ route('home') }}">Trang chủ</a>
        <a href="{{ route('categories') }}">Danh mục</a>
        <a href="{{ route('products') }}">Sản phẩm</a>
        <a href="{{ route('favorites.index') }}">Yêu thích ♡</a>
        <a href="{{ route('cart.index') }}">🛒 Giỏ hàng</a>
    </nav>
</header>

<div class="container">

    <h1>♡ Sản phẩm yêu thích</h1>

    @if (session('success'))
        <div class="success">
            {{ session('success') }}
        </div>
    @endif

    @if ($products->count() > 0)

        <div class="products">
            @foreach ($products as $product)
                <div class="product-card">

                    <div class="product-image">
                        🌸
                    </div>

                    <div class="product-info">

                        <h3>{{ $product->name }}</h3>

                        <p class="category">
                            {{ $product->category->name ?? 'Chưa có danh mục' }}
                        </p>

                        <p class="price">
                            {{ number_format($product->price, 0, ',', '.') }}đ
                        </p>

                        <div class="actions">

                            <a
                                href="{{ route('product.detail', $product->id) }}"
                                class="btn btn-detail"
                            >
                                Xem chi tiết
                            </a>

                            <form
                                action="{{ route('favorites.remove', $product->id) }}"
                                method="POST"
                            >
                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="btn btn-remove"
                                >
                                    Bỏ thích
                                </button>
                            </form>

                        </div>

                    </div>
                </div>
            @endforeach
        </div>

    @else

        <div class="empty">
            <h2>Chưa có sản phẩm yêu thích</h2>

            <p>
                Hãy chọn những sản phẩm bạn yêu thích để xem lại tại đây.
            </p>

            <a href="{{ route('products') }}">
                Khám phá sản phẩm
            </a>
        </div>

    @endif

</div>

</body>
</html>
