@extends('layouts.admin')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Sửa Voucher</h1>

        <a href="{{ route('admin.vouchers.index') }}" class="btn btn-secondary">
            Quay lại
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form
                action="{{ route('admin.vouchers.update', $voucher) }}"
                method="POST"
            >
                @csrf
                @method('PUT')

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            Mã voucher <span class="text-danger">*</span>
                        </label>

                        <input
                            type="text"
                            name="code"
                            class="form-control"
                            value="{{ old('code', $voucher->code) }}"
                            required
                        >
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            Loại giảm giá <span class="text-danger">*</span>
                        </label>

                        <select name="type" class="form-select" required>
                            <option value="percent"
                                {{ old('type', $voucher->type) === 'percent' ? 'selected' : '' }}>
                                Phần trăm (%)
                            </option>

                            <option value="fixed"
                                {{ old('type', $voucher->type) === 'fixed' ? 'selected' : '' }}>
                                Số tiền cố định (VNĐ)
                            </option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            Giá trị giảm <span class="text-danger">*</span>
                        </label>

                        <input
                            type="number"
                            name="value"
                            class="form-control"
                            value="{{ old('value', $voucher->value) }}"
                            min="0"
                            step="0.01"
                            required
                        >
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            Đơn hàng tối thiểu
                        </label>

                        <input
                            type="number"
                            name="min_order_amount"
                            class="form-control"
                            value="{{ old('min_order_amount', $voucher->min_order_amount) }}"
                            min="0"
                            step="0.01"
                        >
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            Giảm tối đa
                        </label>

                        <input
                            type="number"
                            name="max_discount"
                            class="form-control"
                            value="{{ old('max_discount', $voucher->max_discount) }}"
                            min="0"
                            step="0.01"
                        >
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            Số lượt sử dụng tối đa
                        </label>

                        <input
                            type="number"
                            name="usage_limit"
                            class="form-control"
                            value="{{ old('usage_limit', $voucher->usage_limit) }}"
                            min="0"
                            step="1"
                        >
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            Bắt đầu
                        </label>

                        <input
                            type="datetime-local"
                            name="starts_at"
                            class="form-control"
                            value="{{ old('starts_at', $voucher->starts_at?->format('Y-m-d\TH:i')) }}"
                        >
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            Kết thúc
                        </label>

                        <input
                            type="datetime-local"
                            name="expires_at"
                            class="form-control"
                            value="{{ old('expires_at', $voucher->expires_at?->format('Y-m-d\TH:i')) }}"
                        >
                    </div>

                    <div class="col-12 mb-3">
                        <div class="form-check">
                            <input
                                type="hidden"
                                name="is_active"
                                value="0"
                            >

                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                class="form-check-input"
                                id="is_active"
                                {{ old('is_active', $voucher->is_active) ? 'checked' : '' }}
                            >

                            <label class="form-check-label" for="is_active">
                                Kích hoạt voucher
                            </label>
                        </div>
                    </div>

                </div>

                <button type="submit" class="btn btn-primary">
                    Cập nhật
                </button>

                <a
                    href="{{ route('admin.vouchers.index') }}"
                    class="btn btn-secondary"
                >
                    Hủy
                </a>

            </form>

        </div>
    </div>

</div>
@endsection