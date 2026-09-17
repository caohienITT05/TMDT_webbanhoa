@extends('layouts.admin')

@section('title', 'Chi tiết phương thức vận chuyển')

@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h1 class="h3 mb-1">
                Chi tiết phương thức vận chuyển
            </h1>

            <p class="text-muted mb-0">
                Thông tin chi tiết của phương thức vận chuyển.
            </p>
        </div>

        <div class="d-flex gap-2">

            <a href="{{ route('admin.shipping-methods.edit', $shippingMethod) }}"
               class="btn btn-warning">
                Sửa
            </a>

            <a href="{{ route('admin.shipping-methods.index') }}"
               class="btn btn-secondary">
                Quay lại
            </a>

        </div>

    </div>

    <div class="card shadow-sm">

        <div class="card-body">

            <div class="row mb-3">
                <div class="col-md-4 fw-bold">
                    ID
                </div>

                <div class="col-md-8">
                    {{ $shippingMethod->id }}
                </div>
            </div>

            <hr>

            <div class="row mb-3">
                <div class="col-md-4 fw-bold">
                    Tên phương thức
                </div>

                <div class="col-md-8">
                    {{ $shippingMethod->name }}
                </div>
            </div>

            <hr>

            <div class="row mb-3">
                <div class="col-md-4 fw-bold">
                    Phí vận chuyển
                </div>

                <div class="col-md-8">

                    <strong>
                        {{ number_format($shippingMethod->fee, 0, ',', '.') }} ₫
                    </strong>

                </div>
            </div>

            <hr>

            <div class="row mb-3">
                <div class="col-md-4 fw-bold">
                    Mô tả
                </div>

                <div class="col-md-8">
                    {{ $shippingMethod->description ?: 'Không có mô tả.' }}
                </div>
            </div>

            <hr>

            <div class="row mb-3">
                <div class="col-md-4 fw-bold">
                    Trạng thái
                </div>

                <div class="col-md-8">

                    @if($shippingMethod->is_active)

                        <span class="badge bg-success">
                            Đang hoạt động
                        </span>

                    @else

                        <span class="badge bg-secondary">
                            Đã tắt
                        </span>

                    @endif

                </div>
            </div>

            <hr>

            <div class="row mb-3">
                <div class="col-md-4 fw-bold">
                    Ngày tạo
                </div>

                <div class="col-md-8">
                    {{ $shippingMethod->created_at?->format('d/m/Y H:i') }}
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 fw-bold">
                    Cập nhật lần cuối
                </div>

                <div class="col-md-8">
                    {{ $shippingMethod->updated_at?->format('d/m/Y H:i') }}
                </div>
            </div>

        </div>

    </div>

</div>

@endsection