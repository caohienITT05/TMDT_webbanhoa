<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Đơn hàng - BloomGift</title>

    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #fff1f6;
            color: #333;
        }

        .container {
            width: 92%;
            max-width: 1000px;
            margin: 40px auto;
        }

        .logo {
            text-align: center;
            color: #d92d6b;
            font-size: 38px;
            font-weight: bold;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 18px;
            box-shadow: 0 5px 20px rgba(0,0,0,.08);
        }

        .order {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .code {
            font-weight: bold;
            font-size: 19px;
            color: #333;
        }

        .date {
            color: #777;
            margin-top: 8px;
        }

        .status {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 20px;
            background: #ffe0eb;
            color: #d92d6b;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .payment-paid {
            color: #16a34a;
            font-weight: bold;
            margin-top: 5px;
        }

        .price {
            font-size: 19px;
            font-weight: bold;
            color: #d92d6b;
            margin-bottom: 12px;
        }

        .btn {
            display: inline-block;
            padding: 10px 18px;
            border-radius: 8px;
            background: #d92d6b;
            color: white;
            text-decoration: none;
        }

        .btn:hover {
            background: #b51f56;
        }

        .empty {
            text-align: center;
            padding: 40px;
            color: #777;
        }

        /* =========================
           PAGINATION
        ========================= */

        .pagination-wrapper {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #f3dce5;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .pagination a,
        .pagination span {
            min-width: 38px;
            height: 38px;
            padding: 0 12px;
            box-sizing: border-box;

            display: inline-flex;
            justify-content: center;
            align-items: center;

            border: 1px solid #f3c4d5;
            border-radius: 8px;

            background: #fff;
            color: #d92d6b;

            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .pagination a:hover {
            background: #ffe0eb;
            border-color: #d92d6b;
        }

        .pagination .active {
            background: #d92d6b;
            color: white;
            border-color: #d92d6b;
        }

        .pagination .disabled {
            background: #f8f8f8;
            color: #aaa;
            border-color: #eee;
            cursor: not-allowed;
        }

        .pagination-info {
            text-align: center;
            margin-top: 15px;
            color: #777;
            font-size: 14px;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media(max-width:700px) {

            .container {
                width: 94%;
                margin: 20px auto;
            }

            .logo {
                font-size: 30px;
            }

            .card {
                padding: 18px;
            }

            .order {
                flex-direction: column;
                align-items: flex-start;
            }

            .order > div:last-child {
                width: 100%;
                text-align: left !important;
            }

            .price {
                margin-top: 5px;
            }

            .pagination {
                gap: 5px;
            }

            .pagination a,
            .pagination span {
                min-width: 34px;
                height: 34px;
                padding: 0 8px;
                font-size: 13px;
            }
        }
    </style>
</head>

<body>

<div class="container">

    {{-- LOGO --}}
    <div class="logo">
        🌸 BloomGift
    </div>

    <div class="card">

        <h2>Đơn hàng của tôi</h2>

        {{-- =========================
             DANH SÁCH ĐƠN HÀNG
        ========================= --}}

        @forelse($orders as $order)

            @php
                $statusLabels = [
                    'pending' => 'Chờ xác nhận',
                    'confirmed' => 'Đã xác nhận',
                    'processing' => 'Đang xử lý',
                    'shipping' => 'Đang giao',
                    'completed' => 'Hoàn thành',
                    'cancelled' => 'Đã hủy',
                ];
            @endphp

            <div class="card order">

                {{-- THÔNG TIN ĐƠN --}}
                <div>

                    <div class="code">
                        {{ $order->order_code }}
                    </div>

                    <div class="date">
                        Ngày đặt:
                        {{ $order->created_at->format('d/m/Y H:i') }}
                    </div>

                    <div style="margin-top:12px;">
                        Trạng thái:

                        <span class="status">
                            {{ $statusLabels[$order->order_status] ?? $order->order_status }}
                        </span>
                    </div>

                    @if($order->payment_status === 'paid')

                        <div class="payment-paid">
                            ✓ Đã thanh toán
                        </div>

                    @else

                        <div>
                            Chưa thanh toán
                        </div>

                    @endif

                </div>

                {{-- GIÁ + CHI TIẾT --}}
                <div style="text-align:right;">

                    <div class="price">
                        {{ number_format($order->total, 0, ',', '.') }}đ
                    </div>

                    <a
                        href="{{ route('orders.show', $order) }}"
                        class="btn"
                    >
                        Xem chi tiết
                    </a>

                </div>

            </div>

        @empty

            <div class="empty">
                Bạn chưa có đơn hàng nào.
            </div>

        @endforelse


        {{-- =========================
             PAGINATION TỰ TẠO
        ========================= --}}

        @if($orders->hasPages())

            <div class="pagination-wrapper">

                <div class="pagination">

                    {{-- TRANG TRƯỚC --}}
                    @if($orders->onFirstPage())

                        <span class="disabled">
                            ‹ Trước
                        </span>

                    @else

                        <a href="{{ $orders->previousPageUrl() }}">
                            ‹ Trước
                        </a>

                    @endif


                    {{-- CÁC TRANG --}}
                    @foreach($orders->getUrlRange(1, $orders->lastPage()) as $page => $url)

                        @if($page == $orders->currentPage())

                            <span class="active">
                                {{ $page }}
                            </span>

                        @else

                            <a href="{{ $url }}">
                                {{ $page }}
                            </a>

                        @endif

                    @endforeach


                    {{-- TRANG SAU --}}
                    @if($orders->hasMorePages())

                        <a href="{{ $orders->nextPageUrl() }}">
                            Sau ›
                        </a>

                    @else

                        <span class="disabled">
                            Sau ›
                        </span>

                    @endif

                </div>


                {{-- THÔNG TIN PHÂN TRANG --}}
                <div class="pagination-info">

                    Hiển thị
                    <strong>{{ $orders->firstItem() }}</strong>
                    đến
                    <strong>{{ $orders->lastItem() }}</strong>
                    trong tổng số
                    <strong>{{ $orders->total() }}</strong>
                    đơn hàng

                </div>

            </div>

        @endif

    </div>

</div>

</body>
</html>
