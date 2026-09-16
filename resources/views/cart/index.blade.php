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

</head>


<body class="bg-light">


<div class="container py-5">


    {{-- ======================================== --}}
    {{-- TIÊU ĐỀ --}}
    {{-- ======================================== --}}

    <h2 class="mb-4">
        Giỏ hàng của bạn
    </h2>


    {{-- ======================================== --}}
    {{-- THÔNG BÁO --}}
    {{-- ======================================== --}}

    @if(session('success'))

        <div class="alert alert-success">

            {{ session('success') }}

        </div>

    @endif


    @if(session('voucher_success'))

        <div class="alert alert-success">

            {{ session('voucher_success') }}

        </div>

    @endif


    @if(session('voucher_error'))

        <div class="alert alert-danger">

            {{ session('voucher_error') }}

        </div>

    @endif


    @if(session('gift_success'))

        <div class="alert alert-success">

            {{ session('gift_success') }}

        </div>

    @endif


    @if(session('shipping_success'))

        <div class="alert alert-success">

            {{ session('shipping_success') }}

        </div>

    @endif



    {{-- ======================================== --}}
    {{-- GIỎ HÀNG TRỐNG --}}
    {{-- ======================================== --}}

    @if($cartItems->isEmpty())

        <div class="alert alert-info">

            Giỏ hàng đang trống.

        </div>


    @else


        <div class="table-responsive bg-white p-3 shadow-sm rounded">


            {{-- ======================================== --}}
            {{-- DANH SÁCH SẢN PHẨM --}}
            {{-- ======================================== --}}

            <table class="table align-middle">


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

                        <th>
                            Hành động
                        </th>

                    </tr>

                </thead>


                <tbody>


                @foreach($cartItems as $item)


                    <tr>


                        {{-- SẢN PHẨM --}}

                        <td>

                            <strong>

                                ID Sản phẩm:
                                {{ $item->product_id }}

                            </strong>

                        </td>


                        {{-- ĐƠN GIÁ --}}

                        <td>

                            {{ number_format(
                                (float) $item->price,
                                0,
                                ',',
                                '.'
                            ) }}

                            đ

                        </td>


                        {{-- SỐ LƯỢNG --}}

                        <td style="width: 180px;">

                            <form
                                action="{{ route(
                                    'cart.update',
                                    $item->id
                                ) }}"
                                method="POST"
                                class="d-flex"
                            >

                                @csrf

                                @method('PATCH')


                                <input
                                    type="number"
                                    name="quantity"
                                    value="{{ $item->quantity }}"
                                    min="1"
                                    class="form-control me-2"
                                >


                                <button
                                    type="submit"
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    Lưu
                                </button>

                            </form>

                        </td>


                        {{-- THÀNH TIỀN --}}

                        <td>

                            <strong>

                                {{ number_format(
                                    $item->calculated_subtotal,
                                    0,
                                    ',',
                                    '.'
                                ) }}

                                đ

                            </strong>

                        </td>


                        {{-- XÓA --}}

                        <td>

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
                                    class="btn btn-sm btn-danger"
                                >
                                    Xóa
                                </button>

                            </form>

                        </td>


                    </tr>


                @endforeach


                </tbody>


            </table>



            {{-- ======================================== --}}
            {{-- VOUCHER --}}
            {{-- ======================================== --}}

            <div class="card mt-4">


                <div class="card-header">

                    <strong>
                        Mã giảm giá
                    </strong>

                </div>


                <div class="card-body">


                    @if(session('voucher_id'))


                        <div
                            class="d-flex
                            justify-content-between
                            align-items-center"
                        >


                            <div>

                                <span class="text-success">

                                    Voucher đang áp dụng:

                                </span>


                                <strong class="ms-2">

                                    {{ session(
                                        'voucher_code'
                                    ) }}

                                </strong>

                            </div>


                            <form
                                action="{{ route(
                                    'voucher.remove'
                                ) }}"
                                method="POST"
                            >

                                @csrf

                                @method('DELETE')


                                <button
                                    type="submit"
                                    class="btn btn-outline-danger"
                                >
                                    Hủy voucher
                                </button>

                            </form>


                        </div>


                    @else


                        <form
                            action="{{ route(
                                'voucher.apply'
                            ) }}"
                            method="POST"
                            class="row g-2"
                        >

                            @csrf


                            <div class="col-md-8">

                                <input
                                    type="text"
                                    name="code"
                                    class="form-control"
                                    placeholder="Nhập mã voucher"
                                    maxlength="50"
                                    required
                                >

                            </div>


                            <div class="col-md-4">

                                <button
                                    type="submit"
                                    class="btn btn-primary w-100"
                                >
                                    Áp dụng voucher
                                </button>

                            </div>


                        </form>


                    @endif


                </div>


            </div>



            {{-- ======================================== --}}
            {{-- THIỆP + GÓI QUÀ --}}
            {{-- ======================================== --}}

            <div class="card mt-4">


                <div class="card-header">

                    <strong>
                        Thiệp chúc & Gói quà
                    </strong>

                </div>


                <div class="card-body">


                    <form
                        action="{{ route(
                            'gift.save'
                        ) }}"
                        method="POST"
                    >

                        @csrf


                        {{-- THIỆP --}}

                        <h6 class="mb-3">
                            Thiệp chúc
                        </h6>


                        <div class="form-check mb-2">

                            <input
                                class="form-check-input"
                                type="radio"
                                name="gift_card"
                                id="gift_card_none"
                                value="none"

                                {{ session(
                                    'gift_card',
                                    'none'
                                ) === 'none'
                                    ? 'checked'
                                    : '' }}
                            >


                            <label
                                class="form-check-label"
                                for="gift_card_none"
                            >

                                Không chọn thiệp

                                <span class="text-muted">
                                    (0 đ)
                                </span>

                            </label>


                        </div>


                        <div class="form-check mb-3">


                            <input
                                class="form-check-input"
                                type="radio"
                                name="gift_card"
                                id="gift_card_card"
                                value="card"

                                {{ session(
                                    'gift_card',
                                    'none'
                                ) === 'card'
                                    ? 'checked'
                                    : '' }}
                            >


                            <label
                                class="form-check-label"
                                for="gift_card_card"
                            >

                                Thiệp chúc

                                <span class="text-success">
                                    (+10.000 đ)
                                </span>

                            </label>


                        </div>


                        {{-- NỘI DUNG LỜI CHÚC --}}

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
                            >{{ session(
                                'gift_message',
                                ''
                            ) }}</textarea>


                        </div>



                        {{-- GÓI QUÀ --}}

                        <h6 class="mb-3">
                            Gói quà
                        </h6>


                        <div class="form-check mb-2">


                            <input
                                class="form-check-input"
                                type="radio"
                                name="gift_wrap"
                                id="gift_wrap_none"
                                value="none"

                                {{ session(
                                    'gift_wrap',
                                    'none'
                                ) === 'none'
                                    ? 'checked'
                                    : '' }}
                            >


                            <label
                                class="form-check-label"
                                for="gift_wrap_none"
                            >

                                Không gói quà

                                <span class="text-muted">
                                    (0 đ)
                                </span>

                            </label>


                        </div>


                        <div class="form-check mb-2">


                            <input
                                class="form-check-input"
                                type="radio"
                                name="gift_wrap"
                                id="gift_wrap_basic"
                                value="basic"

                                {{ session(
                                    'gift_wrap',
                                    'none'
                                ) === 'basic'
                                    ? 'checked'
                                    : '' }}
                            >


                            <label
                                class="form-check-label"
                                for="gift_wrap_basic"
                            >

                                Gói cơ bản

                                <span class="text-success">
                                    (+20.000 đ)
                                </span>

                            </label>


                        </div>


                        <div class="form-check mb-4">


                            <input
                                class="form-check-input"
                                type="radio"
                                name="gift_wrap"
                                id="gift_wrap_premium"
                                value="premium"

                                {{ session(
                                    'gift_wrap',
                                    'none'
                                ) === 'premium'
                                    ? 'checked'
                                    : '' }}
                            >


                            <label
                                class="form-check-label"
                                for="gift_wrap_premium"
                            >

                                Gói cao cấp

                                <span class="text-success">
                                    (+30.000 đ)
                                </span>

                            </label>


                        </div>


                        <button
                            type="submit"
                            class="btn btn-outline-primary"
                        >

                            Lưu lựa chọn

                        </button>


                    </form>



                    {{-- HỦY THIỆP + GÓI QUÀ --}}

                    @if(
                        session('gift_card') ||
                        session('gift_wrap')
                    )


                        <form
                            action="{{ route(
                                'gift.remove'
                            ) }}"
                            method="POST"
                            class="mt-2"
                        >

                            @csrf

                            @method('DELETE')


                            <button
                                type="submit"
                                class="btn btn-outline-danger"
                            >

                                Hủy thiệp & gói quà

                            </button>


                        </form>


                    @endif


                </div>


            </div>



            {{-- ======================================== --}}
            {{-- PHÍ VẬN CHUYỂN --}}
            {{-- ======================================== --}}

            <div class="card mt-4">


                <div class="card-header">

                    <strong>
                        Phí vận chuyển
                    </strong>

                </div>


                <div class="card-body">


                    <form
                        action="{{ route(
                            'shipping.save'
                        ) }}"
                        method="POST"
                    >

                        @csrf


                        {{-- MIỄN PHÍ --}}

                        <div class="form-check mb-2">


                            <input
                                class="form-check-input"
                                type="radio"
                                name="shipping_fee"
                                id="shipping_free"
                                value="0"

                                {{ (float) session(
                                    'shipping_fee',
                                    0
                                ) === 0.0
                                    ? 'checked'
                                    : '' }}
                            >


                            <label
                                class="form-check-label"
                                for="shipping_free"
                            >

                                Giao hàng tiêu chuẩn

                                <span class="text-success">
                                    (Miễn phí)
                                </span>

                            </label>


                        </div>



                        {{-- 30.000 --}}

                        <div class="form-check mb-2">


                            <input
                                class="form-check-input"
                                type="radio"
                                name="shipping_fee"
                                id="shipping_standard"
                                value="30000"

                                {{ (float) session(
                                    'shipping_fee',
                                    0
                                ) === 30000.0
                                    ? 'checked'
                                    : '' }}
                            >


                            <label
                                class="form-check-label"
                                for="shipping_standard"
                            >

                                Giao hàng tiêu chuẩn có phí

                                <span class="text-success">
                                    (+30.000 đ)
                                </span>

                            </label>


                        </div>



                        {{-- 50.000 --}}

                        <div class="form-check mb-3">


                            <input
                                class="form-check-input"
                                type="radio"
                                name="shipping_fee"
                                id="shipping_fast"
                                value="50000"

                                {{ (float) session(
                                    'shipping_fee',
                                    0
                                ) === 50000.0
                                    ? 'checked'
                                    : '' }}
                            >


                            <label
                                class="form-check-label"
                                for="shipping_fast"
                            >

                                Giao hàng nhanh

                                <span class="text-success">
                                    (+50.000 đ)
                                </span>

                            </label>


                        </div>


                        <button
                            type="submit"
                            class="btn btn-outline-primary"
                        >

                            Cập nhật phí vận chuyển

                        </button>


                    </form>


                </div>


            </div>



            {{-- ======================================== --}}
            {{-- CHI TIẾT THANH TOÁN --}}
            {{-- ======================================== --}}

            <div class="card mt-4">


                <div class="card-header">

                    <strong>
                        Chi tiết thanh toán
                    </strong>

                </div>


                <div class="card-body">


                    {{-- TẠM TÍNH --}}

                    <div
                        class="d-flex
                        justify-content-between
                        mb-2"
                    >

                        <span>
                            Tạm tính:
                        </span>


                        <strong>

                            {{ number_format(
                                $subtotal,
                                0,
                                ',',
                                '.'
                            ) }}

                            đ

                        </strong>

                    </div>



                    {{-- VOUCHER --}}

                    <div
                        class="d-flex
                        justify-content-between
                        mb-2"
                    >

                        <span>
                            Giảm giá voucher:
                        </span>


                        <strong class="text-danger">

                            -{{ number_format(
                                $discount,
                                0,
                                ',',
                                '.'
                            ) }}

                            đ

                        </strong>

                    </div>



                    {{-- THIỆP --}}

                    <div
                        class="d-flex
                        justify-content-between
                        mb-2"
                    >

                        <span>
                            Thiệp chúc:
                        </span>


                        <strong>

                            {{ number_format(
                                $giftCardFee,
                                0,
                                ',',
                                '.'
                            ) }}

                            đ

                        </strong>

                    </div>



                    {{-- GÓI QUÀ --}}

                    <div
                        class="d-flex
                        justify-content-between
                        mb-2"
                    >

                        <span>
                            Gói quà:
                        </span>


                        <strong>

                            {{ number_format(
                                $giftWrapFee,
                                0,
                                ',',
                                '.'
                            ) }}

                            đ

                        </strong>

                    </div>



                    {{-- PHÍ VẬN CHUYỂN --}}

                    <div
                        class="d-flex
                        justify-content-between
                        mb-2"
                    >

                        <span>
                            Phí vận chuyển:
                        </span>


                        <strong>

                            {{ number_format(
                                $shippingFee,
                                0,
                                ',',
                                '.'
                            ) }}

                            đ

                        </strong>

                    </div>



                    <hr>



                    {{-- TOTAL --}}

                    <div
                        class="d-flex
                        justify-content-between"
                    >

                        <span class="fs-5">

                            <strong>
                                Tổng cộng:
                            </strong>

                        </span>


                        <strong
                            class="fs-5 text-success"
                        >

                            {{ number_format(
                                $total,
                                0,
                                ',',
                                '.'
                            ) }}

                            đ

                        </strong>

                    </div>


                </div>


            </div>



            {{-- ======================================== --}}
            {{-- CHECKOUT --}}
            {{-- ======================================== --}}

            <div class="text-end mt-4">


                <a
                    href="{{ route(
                        'checkout.index'
                    ) }}"
                    class="btn btn-success btn-lg"
                >

                    Tiến hành Thanh toán →

                </a>


            </div>


        </div>


    @endif



    {{-- ======================================== --}}
    {{-- FORM TEST THÊM SẢN PHẨM --}}
    {{-- ======================================== --}}

    <div class="card mt-5">


        <div class="card-header bg-secondary text-white">

            Thử nghiệm thêm sản phẩm vào giỏ

        </div>


        <div class="card-body">


            <form
                action="{{ route(
                    'cart.add'
                ) }}"
                method="POST"
                class="row g-3 align-items-center"
            >

                @csrf


                <div class="col-auto">


                    <label class="col-form-label">

                        Product ID:

                    </label>


                    <input
                        type="number"
                        name="product_id"
                        value="5"
                        class="form-control"
                        required
                    >


                </div>



                <div class="col-auto">


                    <label class="col-form-label">

                        Số lượng:

                    </label>


                    <input
                        type="number"
                        name="quantity"
                        value="1"
                        min="1"
                        class="form-control"
                        required
                    >


                </div>



                <div class="col-auto mt-4">


                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        Thêm vào giỏ

                    </button>


                </div>


            </form>


        </div>


    </div>


</div>


</body>

</html>