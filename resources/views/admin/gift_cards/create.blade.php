@extends('layouts.admin')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Thêm Thiệp</h1>

        <a
            href="{{ route('admin.gift-cards.index') }}"
            class="btn btn-secondary"
        >
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
                action="{{ route('admin.gift-cards.store') }}"
                method="POST"
            >
                @csrf

                <div class="mb-3">
                    <label class="form-label">
                        Tên thiệp <span class="text-danger">*</span>
                    </label>

                    <input
                        type="text"
                        name="name"
                        class="form-control"
                        value="{{ old('name') }}"
                        placeholder="VD: Thiệp chúc mừng sinh nhật"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        Giá thiệp <span class="text-danger">*</span>
                    </label>

                    <input
                        type="number"
                        name="price"
                        class="form-control"
                        value="{{ old('price', 0) }}"
                        min="0"
                        step="0.01"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        Hình ảnh
                    </label>

                    <input
                        type="text"
                        name="image"
                        class="form-control"
                        value="{{ old('image') }}"
                        placeholder="VD: gift-cards/birthday.jpg"
                    >

                    <small class="text-muted">
                        Nhập đường dẫn hình ảnh nếu có.
                    </small>
                </div>

                <div class="mb-3">
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
                            {{ old('is_active', true) ? 'checked' : '' }}
                        >

                        <label
                            class="form-check-label"
                            for="is_active"
                        >
                            Kích hoạt thiệp
                        </label>

                    </div>
                </div>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Lưu thiệp
                </button>

                <a
                    href="{{ route('admin.gift-cards.index') }}"
                    class="btn btn-secondary"
                >
                    Hủy
                </a>

            </form>

        </div>
    </div>

</div>
@endsection