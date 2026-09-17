<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Giỏ hàng - BloomGift</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        :root {
            --pink: #d6336c;
            --pink-dark: #b72b59;
            --pink-light: #fff2f7;
            --pink-soft: #fff8fb;
            --pink-border: #f1d4df;
            --text: #3d2731;
            --muted: #8a737d;
            --white: #ffffff;
            --green: #198754;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #fff7fa;
            color: var(--text);
            font-family:
                "Segoe UI",
                Arial,
                sans-serif;
        }

        .cart-page {
            max-width: 1180px;
            margin: 0 auto;
            padding: 45px 20px 70px;
        }

        /* ================================
           HEADER
        ================================= */

        .cart-header {
            margin-bottom: 30px;
        }

        .cart-header h1 {
            margin: 0;
            color: var(--pink);
            font-size: 32px;
            font-weight: 800;
        }

        .cart-header p {
            margin: 8px 0 0;
            color: var(--muted);
            font-size: 15px;
        }

        /* ================================
           ALERT
        ================================= */

        .bloom-alert {
            border: 1px solid var(--pink-border);
            border-radius: 12px;
            background: var(--white);
            color: var(--text);
            padding: 14px 18px;
            margin-bottom: 20px;
        }

        .bloom-alert.success {
            border-color: #b7dfc9;
            background: #f4fff8;
            color: #176b3c;
        }

        .bloom-alert.danger {
            border-color: #f0b9c7;
            background: #fff5f7;
            color: #a52243;
        }

        /* ================================
           EMPTY CART
        ================================= */

        .empty-cart {
            background: var(--white);
            border: 1px solid var(--pink-border);
            border-radius: 20px;
            padding: 65px 25px;
            text-align: center;
            box-shadow: 0 8px 30px rgba(214, 51, 108, 0.07);
        }

        .empty-cart-icon {
            width: 76px;
            height: 76px;
            margin: 0 auto 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--pink-light);
            color: var(--pink);
            font-size: 32px;
        }

        .empty-cart h3 {
            margin-bottom: 8px;
            color: var(--pink);
            font-weight: 700;
        }

        .empty-cart p {
            color: var(--muted);
            margin-bottom: 25px;
        }

        .btn-bloom {
            display: inline-block;
            border: none;
            border-radius: 10px;
            padding: 11px 22px;
            background: var(--pink);
            color: white;
            text-decoration: none;
            font-weight: 700;
            transition: 0.2s ease;
        }

        .btn-bloom:hover {
            background: var(--pink-dark);
            color: white;
            transform: translateY(-1px);
        }

        /* ================================
           CART TABLE
        ================================= */

        .cart-card {
            background: var(--white);
            border: 1px solid var(--pink-border);
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(214, 51, 108, 0.07);
            margin-bottom: 25px;
        }

        .cart-card-header {
            padding: 17px 22px;
            background: var(--pink-light);
            border-bottom: 1px solid var(--pink-border);
        }

        .cart-card-header h5 {
            margin: 0;
            color: var(--pink-dark);
            font-weight: 750;
        }

        .cart-table {
            margin: 0;
        }

        .cart-table thead th {
            padding: 16px 18px;
            background: #fff9fb;
            border-bottom: 1px solid var(--pink-border);
            color: var(--pink-dark);
            font-size: 14px;
            font-weight: 700;
            white-space: nowrap;
        }

        .cart-table tbody td {
            padding: 19px 18px;
            border-color: #f5e6ec;
            vertical-align: middle;
        }

        .cart-table tbody tr:last-child td {
            border-bottom: none;
        }

        .cart-table tbody tr:hover {
            background: #fffafd;
        }

        .product-name {
            color: var(--text);
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .product-code {
            color: var(--muted);
            font-size: 13px;
        }

        .product-price {
            color: var(--pink-dark);
            font-weight: 650;
            white-space: nowrap;
        }

        .product-subtotal {
            color: var(--pink);
            font-weight: 800;
            white-space: nowrap;
        }

        .quantity-form {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .quantity-input {
            width: 75px;
            text-align: center;
            border: 1px solid #e5c8d4;
            border-radius: 8px;
            padding: 8px 6px;
        }

        .quantity-input:focus,
        .form-control:focus {
            border-color: var(--pink);
            box-shadow: 0 0 0 0.18rem rgba(214, 51, 108, 0.12);
        }

        .btn-small {
            border-radius: 8px;
            padding: 7px 12px;
            font-size: 13px;
            font-weight: 650;
        }

        .btn-outline-bloom {
            color: var(--pink);
            background: white;
            border: 1px solid var(--pink);
        }

        .btn-outline-bloom:hover {
            background: var(--pink-light);
            color: var(--pink-dark);
            border-color: var(--pink-dark);
        }

        .btn-remove {
            color: #c0395d;
            background: white;
            border: 1px solid #e9b9c7;
        }

        .btn-remove:hover {
            background: #fff1f4;
            color: #a52343;
            border-color: #d98ca1;
        }

        /* ================================
           OPTION CARDS
        ================================= */

        .option-card {
            background: var(--white);
            border: 1px solid var(--pink-border);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 7px 25px rgba(214, 51, 108, 0.05);
            margin-bottom: 22px;
        }

        .option-header {
            padding: 15px 20px;
            background: var(--pink-light);
            border-bottom: 1px solid var(--pink-border);
        }

        .option-header h5 {
            margin: 0;
            color: var(--pink-dark);
            font-size: 16px;
            font-weight: 750;
        }

        .option-body {
            padding: 20px;
        }

        .option-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 12px 14px;
            margin-bottom: 9px;
            border: 1px solid #eedce4;
            border-radius: 10px;
            background: #fff;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .option-label:hover {
            border-color: #e6b9ca;
            background: var(--pink-soft);
        }

        .option-label-content {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .option-label input {
            accent-color: var(--pink);
        }

        .option-price {
            color: var(--pink);
            font-weight: 700;
            white-space: nowrap;
        }

        .form-label {
            color: var(--text);
            font-weight: 650;
        }

        .form-control {
            border-color: #e5c8d4;
            border-radius: 9px;
            padding: 10px 12px;
        }

        .form-control::placeholder {
            color: #b09aa3;
        }

        /* ================================
           SUMMARY
        ================================= */

        .summary-card {
            background: var(--white);
            border: 1px solid var(--pink-border);
            border-radius: 18px;
            padding: 23px;
            box-shadow: 0 8px 30px rgba(214, 51, 108, 0.08);
        }

        .summary-title {
            color: var(--pink-dark);
            font-size: 18px;
            font-weight: 800;
            margin-bottom: 18px;
        }

        .summary-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 10px 0;
            border-bottom: 1px dashed #ecdde4;
        }

        .summary-row:last-of-type {
            border-bottom: none;
        }

        .summary-label {
            color: #6f5a63;
        }

        .summary-value {
            font-weight: 650;
            white-space: nowrap;
        }

        .discount-value {
            color: #d33b5d;
        }

        .summary-total-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-top: 15px;
            padding-top: 18px;
            border-top: 2px solid var(--pink-border);
        }

        .summary-total-label {
            font-size: 19px;
            font-weight: 800;
        }

        .summary-total {
            color: var(--pink);
            font-size: 25px;
            font-weight: 850;
            white-space: nowrap;
        }

        /* ================================
           CHECKOUT
        ================================= */

        .checkout-area {
            margin-top: 18px;
            display: flex;
            justify-content: flex-end;
        }

        .checkout-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-width: 230px;
            padding: 13px 24px;
            border-radius: 11px;
            background: var(--pink);
            color: white;
            text-decoration: none;
            font-weight: 750;
            box-shadow: 0 6px 18px rgba(214, 51, 108, 0.2);
            transition: 0.2s ease;
        }

        .checkout-button:hover {
            background: var(--pink-dark);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 9px 22px rgba(214, 51, 108, 0.25);
        }

        /* ================================
           BACK TO SHOP
        ================================= */

        .continue-shopping {
            display: inline-block;
            margin-top: 20px;
            color: var(--pink);
            text-decoration: none;
            font-weight: 650;
        }

        .continue-shopping:hover {
            color: var(--pink-dark);
            text-decoration: underline;
        }

        /* ================================
           RESPONSIVE
        ================================= */

        @media (max-width: 768px) {
            .cart-page {
                padding: 30px 12px 50px;
            }

            .cart-header h1 {
                font-size: 27px;
            }

            .cart-table {
                min-width: 760px;
            }

            .checkout-area {
                justify-content: stretch;
            }

            .checkout-button {
                width: 100%;
            }

            .summary-total {
                font-size: 21px;
            }
        }
    </style>
</head>

<body>

<div class="cart-page">

    {{-- ========================================
         TIÊU ĐỀ
    ========================================= --}}

    <div class="cart-header">

        <h1>
            Giỏ hàng của bạn
        </h1>

        <p>
            Kiểm tra sản phẩm và các lựa chọn trước khi thanh toán.
        </p>

    </div>


    {{-- ========================================
         THÔNG BÁO
    ========================================= --}}

    @if(session('success'))

        <div class="bloom-alert success">
            {{ session('success') }}
        </div>

    @endif


    @if(session('voucher_success'))

        <div class="bloom-alert success">
            {{ session('voucher_success') }}
        </div>

    @endif


    @if(session('voucher_error'))

        <div class="bloom-alert danger">
            {{ session('voucher_error') }}
        </div>

    @endif


    @if(session('gift_success'))

        <div class="bloom-alert success">
            {{ session('gift_success') }}
        </div>

    @endif


    @if(session('shipping_success'))

        <div class="bloom-alert success">
            {{ session('shipping_success') }}
        </div>

    @endif


    {{-- ========================================
         GIỎ HÀNG
    ========================================= --}}

    @if($cartItems->isEmpty())

        <div class="empty-cart">

            <div class="empty-cart-icon">
                🛒
            </div>

            <h3>
                Giỏ hàng đang trống
            </h3>

            <p>
                Hãy chọn một sản phẩm xinh xắn từ BloomGift để bắt đầu nhé.
            </p>

            <a
                href="{{ route('products') }}"
                class="btn-bloom"
            >
                Xem sản phẩm
            </a>

        </div>

    @else


        {{-- ========================================
             DANH SÁCH SẢN PHẨM
        ========================================= --}}

        <div class="cart-card">

            <div class="cart-card-header">

                <h5>
                    Sản phẩm trong giỏ
                </h5>

            </div>


            <div class="table-responsive">

                <table class="table cart-table">

                    <thead>

                        <tr>

                            <th>
                                Sản phẩm
                            </th>

                            <th>
                                Đơn giá
                            </th>

                            <th>
                                Số lượng
                            </th>

                            <th>
                                Thành tiền
                            </th>

                            <th class="text-center">
                                Hành động
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @foreach($cartItems as $item)

                            <tr>

                                {{-- SẢN PHẨM --}}

                                <td>

                                    <div class="product-name">

                                        {{ $item->product->name ?? 'Sản phẩm #' . $item->product_id }}

                                    </div>

                                    <div class="product-code">

                                        Mã sản phẩm:
                                        #{{ $item->product_id }}

                                    </div>

                                </td>


                                {{-- ĐƠN GIÁ --}}

                                <td>

                                    <span class="product-price">

                                        {{ number_format(
                                            (float) $item->price,
                                            0,
                                            ',',
                                            '.'
                                        ) }}

                                        đ

                                    </span>

                                </td>


                                {{-- SỐ LƯỢNG --}}

                                <td>

                                    <form
                                        action="{{ route(
                                            'cart.update',
                                            $item->id
                                        ) }}"
                                        method="POST"
                                        class="quantity-form"
                                    >

                                        @csrf

                                        @method('PATCH')


                                        <input
                                            type="number"
                                            name="quantity"
                                            value="{{ $item->quantity }}"
                                            min="1"
                                            class="form-control quantity-input"
                                        >


                                        <button
                                            type="submit"
                                            class="btn btn-small btn-outline-bloom"
                                        >
                                            Lưu
                                        </button>

                                    </form>

                                </td>


                                {{-- THÀNH TIỀN --}}

                                <td>

                                    <span class="product-subtotal">

                                        {{ number_format(
                                            (float) $item->calculated_subtotal,
                                            0,
                                            ',',
                                            '.'
                                        ) }}

                                        đ

                                    </span>

                                </td>


                                {{-- XÓA --}}

                                <td class="text-center">

                                    <form
                                        action="{{ route(
                                            'cart.remove',
                                            $item->id
                                        ) }}"
                                        method="POST"
                                    >

                                        @csrf

                                        @method('DELETE')


                                        <button
                                            type="submit"
                                            class="btn btn-small btn-remove"
                                        >
                                            Xóa
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        </div>


        {{-- ========================================
             VOUCHER
        ========================================= --}}

        <div class="option-card">

            <div class="option-header">

                <h5>
                    Mã giảm giá
                </h5>

            </div>


            <div class="option-body">

                @if(session('voucher_id'))

                    <div
                        class="d-flex justify-content-between align-items-center flex-wrap gap-3"
                    >

                        <div>

                            <span class="text-muted">
                                Voucher đang áp dụng:
                            </span>

                            <strong class="ms-2">
                                {{ session('voucher_code') }}
                            </strong>

                        </div>


                        <form
                            action="{{ route('voucher.remove') }}"
                            method="POST"
                        >

                            @csrf

                            @method('DELETE')

                            <button
                                type="submit"
                                class="btn btn-small btn-remove"
                            >
                                Hủy voucher
                            </button>

                        </form>

                    </div>

                @else

                    <form
                        action="{{ route('voucher.apply') }}"
                        method="POST"
                        class="row g-2"
                    >

                        @csrf

                        <div class="col-md-9">

                            <input
                                type="text"
                                name="code"
                                class="form-control"
                                placeholder="Nhập mã voucher"
                                maxlength="50"
                                required
                            >

                        </div>


                        <div class="col-md-3">

                            <button
                                type="submit"
                                class="btn btn-bloom w-100"
                            >
                                Áp dụng
                            </button>

                        </div>

                    </form>

                @endif

            </div>

        </div>


        {{-- ========================================
             THIỆP + GÓI QUÀ
        ========================================= --}}

        <div class="option-card">

            <div class="option-header">

                <h5>
                    Thiệp chúc & Gói quà
                </h5>

            </div>


            <div class="option-body">

                <form
                    action="{{ route('gift.save') }}"
                    method="POST"
                >

                    @csrf


                    {{-- THIỆP --}}

                    <div class="mb-4">

                        <label class="form-label">
                            Thiệp chúc
                        </label>


                        <label class="option-label">

                            <span class="option-label-content">

                                <input
                                    type="radio"
                                    name="gift_card"
                                    value="none"
                                    {{ session('gift_card', 'none') === 'none'
                                        ? 'checked'
                                        : '' }}
                                >

                                <span>
                                    Không chọn thiệp
                                </span>

                            </span>


                            <span class="option-price">
                                0 đ
                            </span>

                        </label>


                        <label class="option-label">

                            <span class="option-label-content">

                                <input
                                    type="radio"
                                    name="gift_card"
                                    value="card"
                                    {{ session('gift_card', 'none') === 'card'
                                        ? 'checked'
                                        : '' }}
                                >

                                <span>
                                    Thiệp chúc
                                </span>

                            </span>


                            <span class="option-price">
                                +10.000 đ
                            </span>

                        </label>

                    </div>


                    {{-- LỜI CHÚC --}}

                    <div class="mb-4">

                        <label
                            for="gift_message"
                            class="form-label"
                        >
                            Nội dung lời chúc
                        </label>

                        <textarea
                            name="gift_message"
                            id="gift_message"
                            class="form-control"
                            rows="3"
                            maxlength="500"
                            placeholder="Nhập lời chúc muốn gửi..."
                        >{{ session('gift_message', '') }}</textarea>

                    </div>


                    {{-- GÓI QUÀ --}}

                    <div class="mb-3">

                        <label class="form-label">
                            Gói quà
                        </label>


                        <label class="option-label">

                            <span class="option-label-content">

                                <input
                                    type="radio"
                                    name="gift_wrap"
                                    value="none"
                                    {{ session('gift_wrap', 'none') === 'none'
                                        ? 'checked'
                                        : '' }}
                                >

                                <span>
                                    Không gói quà
                                </span>

                            </span>


                            <span class="option-price">
                                0 đ
                            </span>

                        </label>


                        <label class="option-label">

                            <span class="option-label-content">

                                <input
                                    type="radio"
                                    name="gift_wrap"
                                    value="basic"
                                    {{ session('gift_wrap', 'none') === 'basic'
                                        ? 'checked'
                                        : '' }}
                                >

                                <span>
                                    Gói cơ bản
                                </span>

                            </span>


                            <span class="option-price">
                                +20.000 đ
                            </span>

                        </label>


                        <label class="option-label">

                            <span class="option-label-content">

                                <input
                                    type="radio"
                                    name="gift_wrap"
                                    value="premium"
                                    {{ session('gift_wrap', 'none') === 'premium'
                                        ? 'checked'
                                        : '' }}
                                >

                                <span>
                                    Gói cao cấp
                                </span>

                            </span>


                            <span class="option-price">
                                +30.000 đ
                            </span>

                        </label>

                    </div>


                    <button
                        type="submit"
                        class="btn btn-outline-bloom"
                    >
                        Lưu lựa chọn
                    </button>

                </form>


                @if(
                    session('gift_card') ||
                    session('gift_wrap')
                )

                    <form
                        action="{{ route('gift.remove') }}"
                        method="POST"
                        class="mt-3"
                    >

                        @csrf

                        @method('DELETE')

                        <button
                            type="submit"
                            class="btn btn-small btn-remove"
                        >
                            Hủy thiệp & gói quà
                        </button>

                    </form>

                @endif

            </div>

        </div>


        {{-- ========================================
             PHÍ VẬN CHUYỂN
        ========================================= --}}

        <div class="option-card">

            <div class="option-header">

                <h5>
                    Phí vận chuyển
                </h5>

            </div>


            <div class="option-body">

                <form
                    action="{{ route('shipping.save') }}"
                    method="POST"
                >

                    @csrf


                    <label class="option-label">

                        <span class="option-label-content">

                            <input
                                type="radio"
                                name="shipping_fee"
                                value="0"
                                {{ (float) session(
                                    'shipping_fee',
                                    0
                                ) === 0.0
                                    ? 'checked'
                                    : '' }}
                            >

                            <span>
                                Giao hàng tiêu chuẩn
                            </span>

                        </span>


                        <span class="option-price">
                            Miễn phí
                        </span>

                    </label>


                    <label class="option-label">

                        <span class="option-label-content">

                            <input
                                type="radio"
                                name="shipping_fee"
                                value="30000"
                                {{ (float) session(
                                    'shipping_fee',
                                    0
                                ) === 30000.0
                                    ? 'checked'
                                    : '' }}
                            >

                            <span>
                                Giao hàng tiêu chuẩn có phí
                            </span>

                        </span>


                        <span class="option-price">
                            +30.000 đ
                        </span>

                    </label>


                    <label class="option-label">

                        <span class="option-label-content">

                            <input
                                type="radio"
                                name="shipping_fee"
                                value="50000"
                                {{ (float) session(
                                    'shipping_fee',
                                    0
                                ) === 50000.0
                                    ? 'checked'
                                    : '' }}
                            >

                            <span>
                                Giao hàng nhanh
                            </span>

                        </span>


                        <span class="option-price">
                            +50.000 đ
                        </span>

                    </label>


                    <button
                        type="submit"
                        class="btn btn-outline-bloom mt-2"
                    >
                        Cập nhật phí vận chuyển
                    </button>

                </form>

            </div>

        </div>


        {{-- ========================================
             CHI TIẾT THANH TOÁN
        ========================================= --}}

        <div class="summary-card">

            <div class="summary-title">
                Chi tiết thanh toán
            </div>


            {{-- TẠM TÍNH --}}

            <div class="summary-row">

                <span class="summary-label">
                    Tạm tính
                </span>

                <span class="summary-value">

                    {{ number_format(
                        (float) $subtotal,
                        0,
                        ',',
                        '.'
                    ) }}

                    đ

                </span>

            </div>


            {{-- VOUCHER --}}

            <div class="summary-row">

                <span class="summary-label">
                    Giảm giá voucher
                </span>

                <span class="summary-value discount-value">

                    -{{ number_format(
                        (float) $discount,
                        0,
                        ',',
                        '.'
                    ) }}

                    đ

                </span>

            </div>


            {{-- THIỆP --}}

            <div class="summary-row">

                <span class="summary-label">
                    Thiệp chúc
                </span>

                <span class="summary-value">

                    {{ number_format(
                        (float) $giftCardFee,
                        0,
                        ',',
                        '.'
                    ) }}

                    đ

                </span>

            </div>


            {{-- GÓI QUÀ --}}

            <div class="summary-row">

                <span class="summary-label">
                    Gói quà
                </span>

                <span class="summary-value">

                    {{ number_format(
                        (float) $giftWrapFee,
                        0,
                        ',',
                        '.'
                    ) }}

                    đ

                </span>

            </div>


            {{-- PHÍ VẬN CHUYỂN --}}

            <div class="summary-row">

                <span class="summary-label">
                    Phí vận chuyển
                </span>

                <span class="summary-value">

                    {{ number_format(
                        (float) $shippingFee,
                        0,
                        ',',
                        '.'
                    ) }}

                    đ

                </span>

            </div>


            {{-- TỔNG --}}

            <div class="summary-total-row">

                <span class="summary-total-label">
                    Tổng cộng
                </span>

                <span class="summary-total">

                    {{ number_format(
                        (float) $total,
                        0,
                        ',',
                        '.'
                    ) }}

                    đ

                </span>

            </div>


            {{-- CHECKOUT --}}

            <div class="checkout-area">

                <a
                    href="{{ route('checkout') }}"
                    class="checkout-button"
                >
                    Tiến hành thanh toán →
                </a>

            </div>


            <div class="text-end">

                <a
    href="{{ url('/san-pham') }}"
    class="continue-shopping"
>
    ← Tiếp tục mua sắm
</a>

            </div>

        </div>

    @endif

</div>


</body>

</html>