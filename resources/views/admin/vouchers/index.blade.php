<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý Mã Khuyến Mại - BloomGift Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>

<body class="bg-light p-4">
    <div class="container-fluid bg-white rounded shadow-sm p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold text-danger mb-0"><i class="bi bi-ticket-perforated me-2"></i>Danh sách Mã Giảm Giá &
                Flash Sale</h3>
            <div>
                <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary me-2"><i
                        class="bi bi-arrow-left"></i> Dashboard</a>
                <a href="{{ route('admin.vouchers.create') }}" class="btn btn-danger"><i
                        class="bi bi-plus-circle me-1"></i> Tạo Voucher mới</a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="table-responsive">
            <table class="table table-hover align-middle border">
                <thead class="table-danger">
                    <tr>
                        <th>Mã Code</th>
                        <th>Tên chương trình</th>
                        <th>Mức giảm</th>
                        <th>Đơn tối thiểu</th>
                        <th>Khung giờ áp dụng</th>
                        <th>Lượt dùng</th>
                        <th>Trạng thái</th>
                        <th class="text-center">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vouchers as $v)
                        @php
                            $now = now();
                            $isOngoing = $now->between($v->start_time, $v->end_time);
                            $isExpired = $now->gt($v->end_time);
                        @endphp
                        <tr>
                            <td><span class="badge bg-danger fs-6">{{ $v->code }}</span></td>
                            <td><strong>{{ $v->name }}</strong></td>
                            <td class="text-danger fw-bold">
                                {{ $v->type === 'percent' ? $v->value . '%' : number_format($v->value) . ' đ' }}
                                @if($v->max_discount)
                                    <div class="small text-muted">(Tối đa {{ number_format($v->max_discount) }}đ)</div>
                                @endif
                            </td>
                            <td>{{ number_format($v->min_order_amount) }} đ</td>
                            <td>
                                <small class="d-block">Từ:
                                    <strong>{{ $v->start_time->format('H:i d/m/Y') }}</strong></small>
                                <small class="d-block">Đến: <strong>{{ $v->end_time->format('H:i d/m/Y') }}</strong></small>
                            </td>
                            <td>{{ $v->used_count }} / {{ $v->usage_limit }}</td>
                            <td>
                                @if(!$v->is_active)
                                    <span class="badge bg-secondary">Tạm tắt</span>
                                @elseif($isExpired)
                                    <span class="badge bg-dark">Đã hết hạn</span>
                                @elseif($isOngoing)
                                    <span class="badge bg-success">Đang diễn ra</span>
                                @else
                                    <span class="badge bg-warning text-dark">Sắp diễn ra</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('admin.vouchers.edit', $v) }}"
                                    class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>
                                <form action="{{ route('admin.vouchers.destroy', $v) }}" method="POST" class="d-inline"
                                    onsubmit="return confirm('Bạn có chắc chắn muốn xóa?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i
                                            class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Chưa có mã khuyến mại nào. Nhấn "Tạo Voucher
                                mới" để thêm.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $vouchers->links() }}
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>