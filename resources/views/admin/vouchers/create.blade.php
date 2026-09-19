<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Tạo Voucher Khuyến Mại - BloomGift Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light p-4">
    <div class="container bg-white rounded shadow-sm p-4" style="max-width: 750px;">
        <h3 class="text-danger fw-bold mb-4">🎁 Tạo Voucher / Flash Sale Theo Giờ</h3>

        @if ($errors->any())
            <div class="alert alert-danger py-2 small">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.vouchers.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Mã Voucher (Code)</label>
                    <input type="text" name="code" class="form-control text-uppercase"
                        placeholder="VD: BLOOMTRUA, FLASHSALE12" value="{{ old('code') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Tên chương trình khuyến mại</label>
                    <input type="text" name="name" class="form-control" placeholder="VD: Khuyến mại giờ vàng buổi trưa"
                        value="{{ old('name') }}" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Loại giảm giá</label>
                    <select name="type" class="form-select" required>
                        <option value="fixed" {{ old('type') == 'fixed' ? 'selected' : '' }}>Giảm số tiền cố định (VNĐ)
                        </option>
                        <option value="percent" {{ old('type') == 'percent' ? 'selected' : '' }}>Giảm theo phần trăm (%)
                        </option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Mức giảm</label>
                    <input type="number" step="0.01" name="value" class="form-control" placeholder="VD: 50000 hoặc 10"
                        value="{{ old('value') }}" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Đơn hàng tối thiểu (VNĐ)</label>
                    <input type="number" name="min_order_amount" class="form-control"
                        value="{{ old('min_order_amount', 0) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Giảm tối đa (VNĐ - chỉ dành cho %)</label>
                    <input type="number" name="max_discount" class="form-control"
                        placeholder="Để trống nếu không giới hạn" value="{{ old('max_discount') }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Thời gian bắt đầu (Ngày & Giờ)</label>
                    <input type="datetime-local" name="start_time" class="form-control"
                        value="{{ old('start_time', now()->format('Y-m-d\TH:i')) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Thời gian kết thúc (Ngày & Giờ)</label>
                    <input type="datetime-local" name="end_time" class="form-control"
                        value="{{ old('end_time', now()->addDays(3)->format('Y-m-d\TH:i')) }}" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Số lượng phát hành tối đa</label>
                    <input type="number" name="usage_limit" class="form-control" value="{{ old('usage_limit', 100) }}"
                        required>
                </div>
                <div class="col-md-6 d-flex align-items-center mt-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold" for="is_active">Kích hoạt sử dụng ngay</label>
                    </div>
                </div>
            </div>

            <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                <a href="{{ route('admin.vouchers.index') }}" class="btn btn-secondary">Hủy bỏ</a>
                <button type="submit" class="btn btn-danger px-4 fw-bold">Lưu Voucher</button>
            </div>
        </form>
    </div>
</body>

</html>