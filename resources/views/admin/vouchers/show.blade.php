@extends('layouts.admin')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Chi tiết Voucher</h1>

        <div>
            <a
                href="{{ route('admin.vouchers.edit', $voucher) }}"
                class="btn btn-warning"
            >
                Sửa
            </a>

            <a
                href="{{ route('admin.vouchers.index') }}"
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
                    <th width="250">Mã voucher</th>
                    <td>
                        <strong>{{ $voucher->code }}</strong>
                    </td>
                </tr>

                <tr>
                    <th>Loại giảm giá</th>
                    <td>
                        {{ $voucher->type === 'percent' ? 'Phần trăm' : 'Số tiền cố định' }}
                    </td>
                </tr>

                <tr>
                    <th>Giá trị</th>
                    <td>
                        @if($voucher->type === 'percent')
                            {{ number_format($voucher->value, 0, ',', '.') }}%
                        @else
                            {{ number_format($voucher->value, 0, ',', '.') }} đ
                        @endif
                    </td>
                </tr>

                <tr>
                    <th>Đơn hàng tối thiểu</th>
                    <td>
                        {{ number_format($voucher->min_order_amount ?? 0, 0, ',', '.') }} đ
                    </td>
                </tr>

                <tr>
                    <th>Giảm tối đa</th>
                    <td>
                        @if($voucher->max_discount !== null)
                            {{ number_format($voucher->max_discount, 0, ',', '.') }} đ
                        @else
                            Không giới hạn
                        @endif
                    </td>
                </tr>

                <tr>
                    <th>Thời gian bắt đầu</th>
                    <td>
                        {{ $voucher->starts_at?->format('d/m/Y H:i') ?? 'Không giới hạn' }}
                    </td>
                </tr>

                <tr>
                    <th>Thời gian kết thúc</th>
                    <td>
                        {{ $voucher->expires_at?->format('d/m/Y H:i') ?? 'Không giới hạn' }}
                    </td>
                </tr>

                <tr>
                    <th>Số lượt đã sử dụng</th>
                    <td>
                        {{ $voucher->used_count ?? 0 }}
                    </td>
                </tr>

                <tr>
                    <th>Giới hạn sử dụng</th>
                    <td>
                        {{ $voucher->usage_limit ?: 'Không giới hạn' }}
                    </td>
                </tr>

                <tr>
                    <th>Trạng thái</th>
                    <td>
                        @if($voucher->is_active)
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

            </table>

        </div>
    </div>

</div>
@endsection