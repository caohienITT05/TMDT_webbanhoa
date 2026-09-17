<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Thanh toán - BloomGift</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #fff0f5;
            color: #333;
        }

        .container {
            width: 92%;
            max-width: 1150px;
            margin: 40px auto;
        }

        .page-title {
            text-align: center;
            margin-bottom: 30px;
            color: #222;
        }

        .checkout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            align-items: start;
        }

        .box {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }

        .box h2 {
            margin: 0 0 20px;
            font-size: 20px;
            color: #222;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-weight: 700;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 15px;
            background: #fff;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #e11d48;
            box-shadow: 0 0 0 3px rgba(225, 29, 72, 0.1);
        }

        textarea {
            min-height: 95px;
            resize: vertical;
        }

        .required {
            color: #dc2626;
        }

        .payment-option {
            margin-bottom: 14px;
        }

        .payment-option label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            cursor: pointer;
        }

        .payment-option input {
            width: auto;
            margin: 0;
        }

        .summary-product {
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .summary-product-name {
            font-weight: 700;
            color: #333;
            margin-bottom: 4px;
        }

        .summary-product-detail {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            color: #666;
            font-size: 14px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            padding: 9px 0;
            font-size: 15px;
        }

        .summary-item.discount strong {
            color: #16a34a;
        }

        .total {
            border-top: 1px solid #ddd;
            margin-top: 12px;
            padding-top: 16px;
            font-size: 20px;
            font-weight: 700;
            color: #be123c;
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            margin-top: 15px;
            border: none;
            border-radius: 8px;
            background: #111827;
            color: white;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-submit:hover {
            background: #374151;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            padding: 14px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            padding: 14px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .empty-cart {
            background: #f9fafb;
            border: 1px dashed #d1d5db;
            padding: 18px;
            border-radius: 8px;
            color: #6b7280;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #be123c;
            text-decoration: none;
            font-weight: 600;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .error-text {
            color: #dc2626;
            font-size: 14px;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .container {
                width: 94%;
                margin: 20px auto;
            }

            .checkout {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <a href="{{ route('cart.index') }}" class="back-link">
        ← Quay lại giỏ hàng
    </a>

    <h1 class="page-title">
        Thanh toán đơn hàng
    </h1>

    @if(session('error'))
        <div class="alert-error">
            {{ session('error') }}
        </div>
    @endif

    @if(session('success'))
        <div class="alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert-error">
            <strong>Vui lòng kiểm tra lại thông tin:</strong>

            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form
        method="POST"
        action="{{ route('checkout.store') }}"
    >
        @csrf

        <div class="checkout">

            <!-- LEFT -->
            <div>

                <!-- THÔNG TIN NGƯỜI NHẬN -->
                <div class="box">

                    <h2>1. Thông tin người nhận</h2>

                    <div class="form-group">
                        <label>
                            Họ và tên
                            <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            name="recipient_name"
                            value="{{ old('recipient_name') }}"
                            placeholder="Nhập họ tên người nhận"
                            required
                        >

                        @error('recipient_name')
                            <div class="error-text">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label>
                            Số điện thoại
                            <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            name="recipient_phone"
                            value="{{ old('recipient_phone') }}"
                            placeholder="Nhập số điện thoại"
                            required
                        >

                        @error('recipient_phone')
                            <div class="error-text">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label>
                            Địa chỉ nhận hàng
                            <span class="required">*</span>
                        </label>

                        <textarea
                            name="recipient_address"
                            placeholder="Nhập địa chỉ nhận hàng"
                            required
                        >{{ old('recipient_address') }}</textarea>

                        @error('recipient_address')
                            <div class="error-text">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label>
                            Ghi chú
                        </label>

                        <textarea
                            name="note"
                            placeholder="Ví dụ: Gọi trước khi giao, giao tại quầy lễ tân..."
                        >{{ old('note') }}</textarea>

                        @error('note')
                            <div class="error-text">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                </div>

                <!-- THỜI GIAN GIAO -->
                <div class="box">

                    <h2>2. Thời gian giao hàng</h2>

                    <div class="form-group">

                        <label>
                            Ngày giao
                            <span class="required">*</span>
                        </label>

                        <input
                            type="date"
                            name="delivery_date"
                            value="{{ old('delivery_date') }}"
                            min="{{ date('Y-m-d') }}"
                            required
                        >

                        @error('delivery_date')
                            <div class="error-text">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                    <div class="form-group">

                        <label>
                            Khung giờ giao
                            <span class="required">*</span>
                        </label>

                        <select
                            name="delivery_slot_id"
                            required
                        >
                            <option value="">
                                -- Chọn khung giờ --
                            </option>

                            @foreach($deliverySlots as $slot)

                                <option
                                    value="{{ $slot->id }}"
                                    {{ old('delivery_slot_id') == $slot->id ? 'selected' : '' }}
                                >
                                    {{ $slot->name ?? $slot->start_time . ' - ' . $slot->end_time }}
                                </option>

                            @endforeach

                        </select>

                        @error('delivery_slot_id')
                            <div class="error-text">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                </div>

                <!-- THANH TOÁN -->
                <div class="box">

                    <h2>3. Phương thức thanh toán</h2>

                    <div class="payment-option">

                        <label>

                            <input
                                type="radio"
                                name="payment_method"
                                value="cod"
                                {{ old('payment_method', 'cod') === 'cod' ? 'checked' : '' }}
                            >

                            Thanh toán khi nhận hàng (COD)

                        </label>

                    </div>

                    <div class="payment-option">

                        <label>

                            <input
                                type="radio"
                                name="payment_method"
                                value="paypal"
                                {{ old('payment_method') === 'paypal' ? 'checked' : '' }}
                            >

                            Thanh toán bằng PayPal

                        </label>

                    </div>

                    @error('payment_method')
                        <div class="error-text">
                            {{ $message }}
                        </div>
                    @enderror

                </div>

            </div>

            <!-- RIGHT -->
            <div>

                <div class="box">

                    <h2>4. Đơn hàng</h2>

                    @forelse($cartItems as $item)

                        <div class="summary-product">

                            <div class="summary-product-name">
                                {{ $item->product->name }}
                            </div>

                            <div class="summary-product-detail">

                                <span>
                                    {{ $item->quantity }}
                                    ×
                                    {{ number_format(
                                        (float) $item->price,
                                        0,
                                        ',',
                                        '.'
                                    ) }}đ
                                </span>

                                <strong>
                                    {{ number_format(
                                        (float) $item->calculated_subtotal,
                                        0,
                                        ',',
                                        '.'
                                    ) }}đ
                                </strong>

                            </div>

                        </div>

                    @empty

                        <div class="empty-cart">
                            Không có sản phẩm trong đơn hàng.
                        </div>

                    @endforelse

                    <div class="summary-item">

                        <span>
                            Tạm tính
                        </span>

                        <strong>
                            {{ number_format(
                                (float) $subtotal,
                                0,
                                ',',
                                '.'
                            ) }}đ
                        </strong>

                    </div>

                    @if((float) $discount > 0)

                        <div class="summary-item discount">

                            <span>
                                Giảm giá
                            </span>

                            <strong>
                                -
                                {{ number_format(
                                    (float) $discount,
                                    0,
                                    ',',
                                    '.'
                                ) }}đ
                            </strong>

                        </div>

                    @endif

                    @if((float) $giftCardFee > 0)

                        <div class="summary-item">

                            <span>
                                Thiệp
                            </span>

                            <strong>
                                {{ number_format(
                                    (float) $giftCardFee,
                                    0,
                                    ',',
                                    '.'
                                ) }}đ
                            </strong>

                        </div>

                    @endif

                    @if((float) $giftWrapFee > 0)

                        <div class="summary-item">

                            <span>
                                Gói quà
                            </span>

                            <strong>
                                {{ number_format(
                                    (float) $giftWrapFee,
                                    0,
                                    ',',
                                    '.'
                                ) }}đ
                            </strong>

                        </div>

                    @endif

                    <div class="summary-item">

                        <span>
                            Phí giao hàng
                        </span>

                        <strong>
                            {{ number_format(
                                (float) $shippingFee,
                                0,
                                ',',
                                '.'
                            ) }}đ
                        </strong>

                    </div>

                    <div class="summary-item total">

                        <span>
                            Tổng cộng
                        </span>

                        <strong>
                            {{ number_format(
                                (float) $total,
                                0,
                                ',',
                                '.'
                            ) }}đ
                        </strong>

                    </div>

                    <button
                        type="submit"
                        class="btn-submit"
                    >
                        Đặt hàng
                    </button>

                </div>

            </div>

        </div>

    </form>

</div>

</body>
</html>