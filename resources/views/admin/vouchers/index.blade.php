@extends('layouts.admin')

@section('content')

<style>
    .voucher-page {
        padding: 24px 26px;
        background: #f5f6f8;
        min-height: calc(100vh - 70px);
    }

    .voucher-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        overflow: hidden;
    }

    .voucher-header {
        padding: 22px 24px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
    }

    .voucher-title {
        margin: 0;
        font-size: 20px;
        font-weight: 700;
        color: #222;
    }

    .voucher-description {
        margin: 5px 0 0;
        font-size: 13px;
        color: #6b7280;
    }

    .btn-add-voucher {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 9px 16px;
        background: #e91e3f;
        color: #fff;
        border: none;
        border-radius: 6px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
        transition: 0.2s;
        white-space: nowrap;
    }

    .btn-add-voucher:hover {
        background: #d81738;
        color: #fff;
        text-decoration: none;
    }

    .voucher-table-wrap {
        overflow-x: auto;
    }

    .voucher-table {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
    }

    .voucher-table thead th {
        background: #f8f9fb;
        color: #4b5563;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.2px;
        padding: 13px 14px;
        border-bottom: 1px solid #e5e7eb;
        white-space: nowrap;
        text-align: left;
    }

    .voucher-table tbody td {
        padding: 15px 14px;
        border-bottom: 1px solid #eef0f3;
        color: #374151;
        font-size: 13px;
        vertical-align: middle;
    }

    .voucher-table tbody tr:last-child td {
        border-bottom: none;
    }

    .voucher-table tbody tr:hover {
        background: #fafafa;
    }

    .voucher-id {
        color: #6b7280;
        font-weight: 600;
        width: 50px;
    }

    .voucher-code {
        font-weight: 700;
        color: #222;
        white-space: nowrap;
    }

    .voucher-type {
        color: #4b5563;
        white-space: nowrap;
    }

    .voucher-value {
        font-weight: 600;
        color: #222;
        white-space: nowrap;
    }

    .voucher-muted {
        color: #9ca3af;
    }

    .voucher-date {
        color: #4b5563;
        white-space: nowrap;
        line-height: 1.5;
    }

    .voucher-usage {
        white-space: nowrap;
        color: #4b5563;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 5px 9px;
        border-radius: 5px;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
    }

    .status-active {
        background: #ecfdf3;
        color: #15803d;
    }

    .status-inactive {
        background: #f3f4f6;
        color: #6b7280;
    }

    .action-group {
        display: flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
    }

    .action-link {
        padding: 5px 8px;
        border-radius: 5px;
        text-decoration: none;
        font-size: 12px;
        font-weight: 600;
        border: 1px solid transparent;
        transition: 0.2s;
    }

    .action-view {
        color: #4b5563;
    }

    .action-view:hover {
        background: #f3f4f6;
        color: #222;
        text-decoration: none;
    }

    .action-edit {
        color: #d97706;
    }

    .action-edit:hover {
        background: #fff7ed;
        color: #b45309;
        text-decoration: none;
    }

    .action-delete {
        color: #dc2626;
        background: transparent;
        border: none;
        cursor: pointer;
    }

    .action-delete:hover {
        background: #fef2f2;
        color: #b91c1c;
    }

    .empty-voucher {
        text-align: center;
        padding: 55px 20px !important;
        color: #6b7280;
    }

    .empty-voucher-title {
        margin-bottom: 5px;
        font-size: 15px;
        font-weight: 600;
        color: #374151;
    }

    .empty-voucher-text {
        margin: 0;
        font-size: 13px;
    }

    .voucher-pagination {
        padding: 16px 20px;
        border-top: 1px solid #e5e7eb;
    }

    @media (max-width: 900px) {
        .voucher-page {
            padding: 18px;
        }

        .voucher-header {
            align-items: flex-start;
            flex-direction: column;
        }

        .btn-add-voucher {
            width: 100%;
        }
    }
</style>

<div class="voucher-page">

    <div class="voucher-card">

        {{-- HEADER --}}
        <div class="voucher-header">

            <div>
                <h1 class="voucher-title">Tất cả voucher</h1>

                <p class="voucher-description">
                    Quản lý các mã giảm giá của BloomGift
                </p>
            </div>

            <a href="{{ route('admin.vouchers.create') }}"
               class="btn-add-voucher">
                + Thêm voucher mới
            </a>

        </div>

        {{-- TABLE --}}
        <div class="voucher-table-wrap">

            <table class="voucher-table">

                <thead>
                    <tr>
                        <th>#</th>
                        <th>Mã voucher</th>
                        <th>Loại</th>
                        <th>Giá trị</th>
                        <th>Đơn tối thiểu</th>
                        <th>Giảm tối đa</th>
                        <th>Thời gian</th>
                        <th>Đã dùng</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($vouchers as $voucher)

                        <tr>

                            {{-- ID --}}
                            <td class="voucher-id">
                                #{{ $voucher->id }}
                            </td>

                            {{-- CODE --}}
                            <td>
                                <span class="voucher-code">
                                    {{ $voucher->code }}
                                </span>
                            </td>

                            {{-- TYPE --}}
                            <td class="voucher-type">

                                @if($voucher->type === 'percent')
                                    Phần trăm
                                @else
                                    Số tiền
                                @endif

                            </td>

                            {{-- VALUE --}}
                            <td class="voucher-value">

                                @if($voucher->type === 'percent')

                                    {{ rtrim(rtrim(number_format((float) $voucher->value, 2, ',', '.'), '0'), ',') }}%

                                @else

                                    {{ number_format((float) $voucher->value, 0, ',', '.') }} ₫

                                @endif

                            </td>

                            {{-- MIN ORDER --}}
                            <td>

                                @if((float) $voucher->min_order_amount > 0)

                                    {{ number_format((float) $voucher->min_order_amount, 0, ',', '.') }} ₫

                                @else

                                    <span class="voucher-muted">—</span>

                                @endif

                            </td>

                            {{-- MAX DISCOUNT --}}
                            <td>

                                @if($voucher->max_discount !== null && (float) $voucher->max_discount > 0)

                                    {{ number_format((float) $voucher->max_discount, 0, ',', '.') }} ₫

                                @else

                                    <span class="voucher-muted">—</span>

                                @endif

                            </td>

                            {{-- TIME --}}
                            <td class="voucher-date">

                                <div>
                                    {{ $voucher->starts_at
                                        ? $voucher->starts_at->format('d/m/Y H:i')
                                        : 'Không giới hạn' }}
                                </div>

                                <div>
                                    →
                                    {{ $voucher->expires_at
                                        ? $voucher->expires_at->format('d/m/Y H:i')
                                        : 'Không giới hạn' }}
                                </div>

                            </td>

                            {{-- USAGE --}}
                            <td class="voucher-usage">

                                {{ $voucher->used_count ?? 0 }}

                                @if($voucher->usage_limit !== null)
                                    / {{ $voucher->usage_limit }}
                                @else
                                    / ∞
                                @endif

                            </td>

                            {{-- STATUS --}}
                            <td>

                                @if($voucher->is_active)

                                    <span class="status-badge status-active">
                                        Đang hoạt động
                                    </span>

                                @else

                                    <span class="status-badge status-inactive">
                                        Ngừng hoạt động
                                    </span>

                                @endif

                            </td>

                            {{-- ACTION --}}
                            <td>

                                <div class="action-group">

                                    <a href="{{ route('admin.vouchers.show', $voucher) }}"
                                       class="action-link action-view">
                                        Xem
                                    </a>

                                    <a href="{{ route('admin.vouchers.edit', $voucher) }}"
                                       class="action-link action-edit">
                                        Sửa
                                    </a>

                                    <form action="{{ route('admin.vouchers.destroy', $voucher) }}"
                                          method="POST"
                                          style="display:inline;"
                                          onsubmit="return confirm('Bạn có chắc muốn xóa voucher này không?');">

                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                                class="action-link action-delete">
                                            Xóa
                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="10" class="empty-voucher">

                                <div class="empty-voucher-title">
                                    Chưa có voucher nào
                                </div>

                                <p class="empty-voucher-text">
                                    Hãy thêm voucher đầu tiên cho cửa hàng.
                                </p>

                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        {{-- PAGINATION --}}
        @if($vouchers->hasPages())

            <div class="voucher-pagination">
                {{ $vouchers->links() }}
            </div>

        @endif

    </div>

</div>

@endsection
