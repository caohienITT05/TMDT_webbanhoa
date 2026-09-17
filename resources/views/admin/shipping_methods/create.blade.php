@extends('layouts.admin')

@section('title', 'Thêm phương thức vận chuyển')

@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Thêm phương thức vận chuyển</h1>
            <p class="text-muted mb-0">
                Tạo phương thức vận chuyển mới cho khách hàng.
            </p>
        </div>

        <a href="{{ route('admin.shipping-methods.index') }}"
           class="btn btn-secondary">
            Quay lại
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Có lỗi xảy ra:</strong>

            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">

            <form action="{{ route('admin.shipping-methods.store') }}"
                  method="POST">

                @csrf

                <div class="mb-3">
                    <label for="name" class="form-label">
                        Tên phương thức vận chuyển
                        <span class="text-danger">*</span>
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        class="form-control"
                        value="{{ old('name') }}"
                        placeholder="Ví dụ: Giao hàng tiêu chuẩn"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="fee" class="form-label">
                        Phí vận chuyển
                        <span class="text-danger">*</span>
                    </label>

                    <div class="input-group">
                        <input
                            type="number"
                            id="fee"
                            name="fee"
                            class="form-control"
                            value="{{ old('fee', 0) }}"
                            min="0"
                            step="1000"
                            required
                        >

                        <span class="input-group-text">₫</span>
                    </div>

                    <small class="text-muted">
                        Nhập số tiền vận chuyển, ví dụ: 30000.
                    </small>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">
                        Mô tả
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        class="form-control"
                        rows="4"
                        placeholder="Ví dụ: Giao hàng trong ngày tại khu vực nội thành."
                    >{{ old('description') }}</textarea>
                </div>

                <div class="mb-4 form-check">
                    <input
                        type="checkbox"
                        id="is_active"
                        name="is_active"
                        value="1"
                        class="form-check-input"
                        {{ old('is_active', true) ? 'checked' : '' }}
                    >

                    <label for="is_active" class="form-check-label">
                        Kích hoạt phương thức vận chuyển
                    </label>
                </div>

                <div class="d-flex gap-2">

                    <button type="submit"
                            class="btn btn-primary">
                        Lưu phương thức
                    </button>

                    <a href="{{ route('admin.shipping-methods.index') }}"
                       class="btn btn-secondary">
                        Hủy
                    </a>

                </div>

            </form>

        </div>
    </div>

</div>

@endsection