<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ hàng - BloomGift Tiệm Hoa Tươi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-rose: #e11d48;
            --soft-rose: #fff0f3;
            --border-rose: #ffe4e6;
        }

        body {
            background-color: #fffafb;
            color: #374151;
            font-family: system-ui, -apple-system, sans-serif;
        }

        .table thead th {
            background-color: var(--soft-rose);
            color: #9f1239;
            font-weight: 600;
            border-bottom: 2px solid var(--border-rose);
            padding: 14px 16px;
        }

        .btn-rose {
            background-color: var(--primary-rose);
            color: #fff;
            border: none;
            font-weight: 600;
        }

        .btn-rose:hover {
            background-color: #be123c;
            color: #fff;
        }

        .card-custom {
            border: 1px solid var(--border-rose);
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(225, 29, 72, 0.04);
        }
    </style>
</head>

<body>

    <div class="container py-5">

        <!-- Tiêu đề & Điều hướng quay lại -->
        <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
            <h2 class="h4 fw-bold text-dark mb-0">
                🌸 Giỏ hàng của bạn
            </h2>
            <a href="{{ url('/') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                &larr; Tiếp tục chọn hoa
            </a>
        </div>

        <!-- Thông báo thao tác -->
        @if(session('success'))
            <div class="alert alert-success border-0 shadow-sm rounded-3 py-2 px-3 small">
                ✅ {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger border-0 shadow-sm rounded-3 py-2 px-3 small">
                ⚠️ {{ session('error') }}
            </div>
        @endif

        @if($cartItems->isEmpty())
            <!-- Trạng thái giỏ hàng trống -->
            <div class="card card-custom bg-white p-5 text-center my-4">
                <div class="display-3 mb-3">🌷</div>
                <h5 class="fw-bold text-secondary">Giỏ hàng của bạn đang trống</h5>
                <p class="text-muted small">Hãy dạo quanh cửa hàng và chọn những bó hoa tươi thắm nhất nhé!</p>
                <div class="mt-2">
                    <a href="{{ url('/') }}" class="btn btn-rose rounded-pill px-4 py-2">
                        Khám phá các mẫu hoa
                    </a>
                </div>
            </div>
        @else
            <div class="row g-4">
                <!-- Cột trái: Danh sách hoa trong giỏ -->
                <div class="col-lg-8">
                    <div class="card card-custom bg-white overflow-hidden p-0">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col" style="min-width: 250px;">Sản phẩm hoa</th>
                                        <th scope="col" class="text-center">Đơn giá</th>
                                        <th scope="col" class="text-center" style="width: 140px;">Số lượng</th>
                                        <th scope="col" class="text-end">Thành tiền</th>
                                        <th scope="col" class="text-center" style="width: 60px;">Xóa</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($cartItems as $item)
                                        <tr>
                                            <!-- Tên & Ảnh hoa -->
                                            <td>
                                                <div class="d-flex align-items-center gap-3 py-1">
                                                    @if($item->product && $item->product->image)
                                                        <img src="{{ asset('storage/' . $item->product->image) }}"
                                                            alt="{{ $item->product->name }}" class="rounded-3 border"
                                                            style="width: 64px; height: 64px; object-fit: cover;">
                                                    @else
                                                        <div class="rounded-3 d-flex align-items-center justify-content-center border"
                                                            style="width: 64px; height: 64px; background-color: #fff0f3; color: #fb7185;">
                                                            🌸
                                                        </div>
                                                    @endif

                                                    <div>
                                                        @if($item->product)
                                                            <a href="{{ route('product.detail', $item->product->slug ?? $item->product->id) }}"
                                                                class="fw-bold text-decoration-none text-dark d-block">
                                                                {{ $item->product->name }}
                                                            </a>
                                                            <small class="text-muted">
                                                                {{ $item->product->category->name ?? 'Hoa tươi thiết kế' }}
                                                            </small>
                                                        @else
                                                            <span class="text-danger small fw-semibold">Sản phẩm không khả dụng
                                                                (#{{ $item->product_id }})</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Đơn giá -->
                                            <td class="text-center text-secondary">
                                                {{ number_format((float) $item->price, 0, ',', '.') }} đ
                                            </td>

                                            <!-- Form cập nhật số lượng tự động -->
                                            <form action="{{ route('cart.update', $item->id) }}" method="POST"
                                                class="d-inline-block">
                                                @csrf
                                                @method('PATCH')
                                                <div class="input-group input-group-sm" style="width: 110px; margin: 0 auto;">
                                                    <input type="number" name="quantity" value="{{ $item->quantity }}" min="1"
                                                        max="99" onchange="this.form.submit()"
                                                        class="form-control text-center fw-bold border-rose-200"
                                                        title="Thay đổi số lượng để tự động tính lại tiền">
                                                </div>
                                            </form>

                                            <!-- Thành tiền -->
                                            <td class="text-end fw-bold text-dark">
                                                {{ number_format($item->calculated_subtotal ?? ($item->price * $item->quantity), 0, ',', '.') }}
                                                đ
                                            </td>

                                            <!-- Xóa khỏi giỏ -->
                                            <td class="text-center">
                                                <form action="{{ route('cart.remove', $item->id) }}" method="POST"
                                                    onsubmit="return confirm('Xóa mẫu hoa này khỏi giỏ?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="btn btn-sm btn-link text-danger p-0 border-0 text-decoration-none"
                                                        title="Xóa hoa">
                                                        🗑️
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Cột phải: Tóm tắt đơn & Tiến hành thanh toán -->
                <div class="col-lg-4">
                    <div class="card card-custom bg-white p-4">
                        <h5 class="fw-bold text-dark pb-2 border-bottom mb-3">Tóm tắt đơn hoa</h5>

                        <div class="d-flex justify-content-between mb-3 text-secondary small">
                            <span>Tạm tính ({{ $cartItems->sum('quantity') }} sản phẩm):</span>
                            <span class="fw-bold text-dark fs-6">{{ number_format($subtotal, 0, ',', '.') }} đ</span>
                        </div>

                        <div class="p-2.5 rounded-3 mb-3 small"
                            style="background-color: var(--soft-rose); color: #9f1239; line-height: 1.5;">
                            🌿 <em>Tùy chọn thiệp chúc mừng, gói quà cao cấp và mã giảm giá sẽ được chọn ở bước tiếp
                                theo.</em>
                        </div>

                        <hr class="my-3 border-secondary-subtle">

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <span class="fw-bold">Tổng tiền giỏ hàng:</span>
                            <span class="fs-5 fw-bold text-danger">{{ number_format($subtotal, 0, ',', '.') }} đ</span>
                        </div>

                        <!-- Nút CTA chuyển sang Checkout -->
                        <a href="{{ route('checkout.index') }}"
                            class="btn btn-rose btn-lg w-100 rounded-pill py-2.5 shadow-sm text-center">
                            Tiến hành Thanh toán &rarr;
                        </a>
                    </div>
                </div>
            </div>
        @endif

    </div>

</body>

</html>