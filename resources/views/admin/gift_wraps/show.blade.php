@extends('layouts.admin')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Chi tiết Gói quà</h1>

        <div>
            <a
                href="{{ route('admin.gift-wraps.edit', $giftWrap) }}"
                class="btn btn-warning"
            >
                Sửa
            </a>

            <a
                href="{{ route('admin.gift-wraps.index') }}"
                class="btn btn-secondary"
            >
                Quay lại
            </a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">

            <table class="table table-bordered">

                <tr>
                    <th width="250">Tên gói quà</th>
                    <td>{{ $giftWrap->name }}</td>
                </tr>

                <tr>
                    <th>Giá</th>
                    <td>
                        {{ number_format($giftWrap->price, 0, ',', '.') }} đ
                    </td>
                </tr>

                <tr>
                    <th>Hình ảnh</th>
                    <td>
                        @if($giftWrap->image)
                            <img
                                src="{{ asset('storage/' . $giftWrap->image) }}"
                                alt="{{ $giftWrap->name }}"
                                width="180"
                                height="180"
                                style="object-fit: cover;"
                                class="rounded"
                            >
                        @else
                            <span class="text-muted">
                                Chưa có hình ảnh
                            </span>
                        @endif
                    </td>
                </tr>

                <tr>
                    <th>Trạng thái</th>
                    <td>
                        @if($giftWrap->is_active)
                            <span class="badge bg-success">
                                Đang hoạt động
                            </span>
                        @else
                            <span class="badge bg-secondary">
                                Tạm khóa
                            </span>
                        @endif
                    </td>
                </tr>

                <tr>
                    <th>Ngày tạo</th>
                    <td>
                        {{ $giftWrap->created_at?->format('d/m/Y H:i') }}
                    </td>
                </tr>

                <tr>
                    <th>Cập nhật lần cuối</th>
                    <td>
                        {{ $giftWrap->updated_at?->format('d/m/Y H:i') }}
                    </td>
                </tr>

            </table>

        </div>
    </div>

</div>
@endsection