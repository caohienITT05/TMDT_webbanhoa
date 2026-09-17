@extends('layouts.admin')

@section('content')

<style>
    .gift-page {
        padding: 24px 26px;
        background: #f5f6f8;
        min-height: calc(100vh - 70px);
    }

    .gift-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        overflow: hidden;
    }

    .gift-header {
        padding: 22px 24px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
    }

    .gift-title {
        margin: 0;
        font-size: 20px;
        font-weight: 700;
        color: #222;
    }

    .gift-description {
        margin: 5px 0 0;
        font-size: 13px;
        color: #6b7280;
    }

    .btn-add-gift {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 9px 16px;
        background: #e91e3f;
        color: #fff;
        border: none;
        border-radius: 6px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
        white-space: nowrap;
    }

    .btn-add-gift:hover {
        background: #d81738;
        color: #fff;
        text-decoration: none;
    }

    .gift-table-wrap {
        overflow-x: auto;
    }

    .gift-table {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
    }

    .gift-table thead th {
        background: #f8f9fb;
        color: #4b5563;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        padding: 13px 14px;
        border-bottom: 1px solid #e5e7eb;
        white-space: nowrap;
        text-align: left;
    }

    .gift-table tbody td {
        padding: 15px 14px;
        border-bottom: 1px solid #eef0f3;
        color: #374151;
        font-size: 13px;
        vertical-align: middle;
    }

    .gift-table tbody tr:last-child td {
        border-bottom: none;
    }

    .gift-table tbody tr:hover {
        background: #fafafa;
    }

    .gift-id {
        color: #6b7280;
        font-weight: 600;
        width: 55px;
    }

    .gift-name {
        font-weight: 600;
        color: #222;
    }

    .gift-price {
        font-weight: 600;
        color: #222;
        white-space: nowrap;
    }

    .gift-image {
        width: 58px;
        height: 45px;
        object-fit: cover;
        border-radius: 5px;
        border: 1px solid #e5e7eb;
        display: block;
    }

    .no-image {
        color: #9ca3af;
        font-size: 12px;
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
        gap: 5px;
        white-space: nowrap;
    }

    .action-link {
        padding: 5px 8px;
        border-radius: 5px;
        border: none;
        background: transparent;
        font-size: 12px;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
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
    }

    .action-delete:hover {
        background: #fef2f2;
        color: #b91c1c;
    }

    .empty-gift {
        text-align: center;
        padding: 55px 20px !important;
        color: #6b7280;
    }

    .empty-gift-title {
        margin-bottom: 5px;
        font-size: 15px;
        font-weight: 600;
        color: #374151;
    }

    .empty-gift-text {
        margin: 0;
        font-size: 13px;
    }

    .gift-pagination {
        padding: 16px 20px;
        border-top: 1px solid #e5e7eb;
    }

    @media (max-width: 800px) {
        .gift-page {
            padding: 18px;
        }

        .gift-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .btn-add-gift {
            width: 100%;
        }
    }
</style>

<div class="gift-page">

    <div class="gift-card">

        <div class="gift-header">

            <div>
                <h1 class="gift-title">Tất cả thiệp</h1>

                <p class="gift-description">
                    Quản lý các mẫu thiệp chúc mừng của BloomGift
                </p>
            </div>

            <a href="{{ route('admin.gift-cards.create') }}"
               class="btn-add-gift">
                + Thêm thiệp mới
            </a>

        </div>

        <div class="gift-table-wrap">

            <table class="gift-table">

                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tên thiệp</th>
                        <th>Giá</th>
                        <th>Hình ảnh</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($giftCards as $giftCard)

                        <tr>

                            <td class="gift-id">
                                #{{ $giftCard->id }}
                            </td>

                            <td>
                                <span class="gift-name">
                                    {{ $giftCard->name }}
                                </span>
                            </td>

                            <td class="gift-price">
                                {{ number_format((float) $giftCard->price, 0, ',', '.') }} ₫
                            </td>

                            <td>

                                @if($giftCard->image)

                                    <img
                                        src="{{ asset('storage/' . $giftCard->image) }}"
                                        alt="{{ $giftCard->name }}"
                                        class="gift-image"
                                    >

                                @else

                                    <span class="no-image">
                                        Chưa có ảnh
                                    </span>

                                @endif

                            </td>

                            <td>

                                @if($giftCard->is_active)

                                    <span class="status-badge status-active">
                                        Đang hoạt động
                                    </span>

                                @else

                                    <span class="status-badge status-inactive">
                                        Ngừng hoạt động
                                    </span>

                                @endif

                            </td>

                            <td>

                                <div class="action-group">

                                    <a
                                        href="{{ route('admin.gift-cards.show', $giftCard) }}"
                                        class="action-link action-view"
                                    >
                                        Xem
                                    </a>

                                    <a
                                        href="{{ route('admin.gift-cards.edit', $giftCard) }}"
                                        class="action-link action-edit"
                                    >
                                        Sửa
                                    </a>

                                    <form
                                        action="{{ route('admin.gift-cards.destroy', $giftCard) }}"
                                        method="POST"
                                        style="display:inline;"
                                        onsubmit="return confirm('Bạn có chắc muốn xóa thiệp này không?');"
                                    >

                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="action-link action-delete"
                                        >
                                            Xóa
                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="6" class="empty-gift">

                                <div class="empty-gift-title">
                                    Chưa có mẫu thiệp nào
                                </div>

                                <p class="empty-gift-text">
                                    Hãy thêm mẫu thiệp đầu tiên cho cửa hàng.
                                </p>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        @if(method_exists($giftCards, 'hasPages') && $giftCards->hasPages())

            <div class="gift-pagination">
                {{ $giftCards->links() }}
            </div>

        @endif

    </div>

</div>

@endsection
