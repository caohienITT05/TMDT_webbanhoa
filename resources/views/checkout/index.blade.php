<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Checkout - BloomGift</title>

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
            width: 90%;
            max-width: 1100px;
            margin: 40px auto;
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
        }

        .checkout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
        }

        .box {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }

        .box h2 {
            margin-top: 0;
            font-size: 20px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-weight: bold;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
        }

        textarea {
            min-height: 90px;
            resize: vertical;
        }

        .payment-option {
            margin-bottom: 12px;
        }

        .payment-option input {
            width: auto;
            margin-right: 8px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
        }

        .total {
            border-top: 1px solid #ddd;
            margin-top: 15px;
            padding-top: 15px;
            font-size: 20px;
            font-weight: bold;
        }

        button {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 7px;
            background: #222;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #444;
        }

        .required {
            color: red;
        }

        @media (max-width: 768px) {
            .checkout {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <h1>🌸 BloomGift - Thanh toán</h1>

    <form action="{{ route('checkout.store') }}" method="POST">

        @csrf

        <div class="checkout">

            <!-- LEFT -->
            <div>

                <div class="box">
                    <h2>1. Thông tin người nhận</h2>

                    <div class="form-group">
                        <label>
                            Họ và tên <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            name="recipient_name"
                            placeholder="Nhập họ tên người nhận"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label>
                            Số điện thoại <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            name="recipient_phone"
                            placeholder="Nhập số điện thoại"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label>
                            Địa chỉ nhận hàng <span class="required">*</span>
                        </label>

                        <textarea
                            name="recipient_address"
                            placeholder="Nhập địa chỉ nhận hàng"
                            required
                        ></textarea>
                    </div>

                    <div class="form-group">
                        <label>Ghi chú</label>

                        <textarea
                            name="note"
                            placeholder="Ví dụ: Giao vào buổi tối, gọi trước khi giao..."
                        ></textarea>
                    </div>
                </div>

                <div class="box">
                    <h2>2. Thời gian giao hàng</h2>

                    <div class="form-group">
                        <label>
                            Ngày giao <span class="required">*</span>
                        </label>

                        <input
                            type="date"
                            name="delivery_date"
                            min="{{ date('Y-m-d') }}"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label>
                            Khung giờ giao <span class="required">*</span>
                        </label>

                        <select name="delivery_slot_id" required>

                            <option value="">
                                -- Chọn khung giờ --
                            </option>

                            @foreach($deliverySlots as $slot)

                                <option value="{{ $slot->id }}">
                                    {{ $slot->name }}
                                    ({{ substr($slot->start_time, 0, 5) }}
                                    -
                                    {{ substr($slot->end_time, 0, 5) }})
                                </option>

                            @endforeach

                        </select>
                    </div>
                </div>

                <div class="box">
                    <h2>3. Phương thức thanh toán</h2>

                    <div class="payment-option">

                        <label>
                            <input
                                type="radio"
                                name="payment_method"
                                value="cod"
                                checked
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
                            >

                            Thanh toán bằng PayPal
                        </label>

                    </div>
                </div>

            </div>

            <!-- RIGHT -->
            <div>

                <div class="box">

                    <h2>Đơn hàng</h2>

                    <div class="summary-item">
                        <span>Hoa hồng đỏ × 1</span>
                        <span>350.000đ</span>
                    </div>

                    <div class="summary-item">
                        <span>Hoa baby × 1</span>
                        <span>150.000đ</span>
                    </div>

                    <div class="summary-item">

                        <span>Tạm tính</span>

                        <span>
                            500.000đ
                        </span>

                    </div>

                    <div class="summary-item">

                        <span>Phí giao hàng</span>

                        <span>
                            30.000đ
                        </span>

                    </div>

                    <div class="summary-item">

                        <span>Giảm giá</span>

                        <span>
                            0đ
                        </span>

                    </div>

                    <div class="summary-item total">

                        <span>Tổng cộng</span>

                        <span>
                            530.000đ
                        </span>

                    </div>

                    <br>

                    <button type="submit">
                        Đặt hàng
                    </button>

                </div>

            </div>

        </div>

    </form>

</div>

</body>
</html>
