
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ hàng - BloomGift</title>

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

        header {
            background: white;
            padding: 20px 8%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #f3dce6;
        }

        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #c94f7c;
        }

        nav a {
            margin-left: 22px;
            text-decoration: none;
            color: #573c49;
            font-weight: bold;
        }

        .container {
            width: 84%;
            margin: 40px auto;
        }

        h1 {
            color: #b83d6d;
            text-align: center;
        }

        .message {
            background: #e4f7e8;
            color: #28663b;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .cart-box {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 18px rgba(180, 90, 120, 0.1);
        }

        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            padding: 18px 0;
            border-bottom: 1px solid #f1dce5;
        }

        .cart-item h3 {
            margin: 0 0 8px;
            color: #b83d6d;
        }

        .price {
            color: #d33c72;
            font-weight: bold;
        }

        .remove-button {
            background: #e0527f;
            color: white;
            border: none;
            padding: 9px 13px;
            border-radius: 7px;
            cursor: pointer;
        }

        .total {
            text-align: right;
            color: #b83d6d;
            font-size: 23px;
            font-weight: bold;
            margin-top: 25px;
        }

        .back-link {
            display: inline-block;
            margin-top: 25px;
            color: #b83d6d;
            text-decoration: none;
            font-weight: bold;
        }

        .empty {
            text-align: center;
            padding: 35px;
        }

        @media (max-width: 700px) {
            header {
                flex-direction: column;
                gap: 15px;
            }

            .cart-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .container {
                width: 92%;
            }
        }
    </style>
</head>

<body>
    <header>
        <div class="logo">BloomGift</div>

        <nav>
            <a href="{{ route('home') }}">Trang chủ</a>
            <a href="{{ route('products') }}">Sản phẩm</a>
            <a href="{{ route('cart.index') }}">Giỏ hàng</a>
        </nav>
    </header>

    <main class="container">
        <h1>Giỏ hàng của bạn</h1>

        @if (session('success'))
            <div class="message">
                {{ session('success') }}
            </div>
        @endif

        <div class="cart-box">
            @if (count($cart) > 0)
                @foreach ($cart as $id => $item)
                    <div class="cart-item">
                        <div>
                            <h3>{{ $item['name'] }}</h3>

                            <div>
                                Số lượng: {{ $item['quantity'] }}
                            </div>

                            <div class="price">
                                {{ number_format($item['price'], 0, ',', '.') }} VNĐ
                            </div>
                        </div>

                        <form
                            action="{{ route('cart.remove', $id) }}"
                            method="POST"
                        >
                            @csrf
                            @method('DELETE')

                            <button class="remove-button" type="submit">
                                Xóa
                            </button>
                        </form>
                    </div>
                @endforeach

                <div class="total">
                    Tổng tiền:
                    {{ number_format($total, 0, ',', '.') }} VNĐ
                </div>
            @else
                <div class="empty">
                    <h2>Giỏ hàng đang trống</h2>
                    <p>Hãy chọn một sản phẩm yêu thích của bạn nhé!</p>
                </div>
            @endif
        </div>

        <a class="back-link" href="{{ route('products') }}">
            ← Tiếp tục mua hàng
        </a>
    </main>
</body>
</html>
