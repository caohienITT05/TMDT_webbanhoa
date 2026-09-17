<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Quản lý đơn hàng - BloomGift</title>

    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #fff1f6;
            color: #333;
        }

        .container {
            width: 95%;
            max-width: 1200px;
            margin: 40px auto;
        }

        h1 {
            color: #d92d6b;
        }

        .card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,.08);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 14px 10px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }

        th {
            background: #ffe0eb;
            color: #b51f56;
        }

        .status {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 20px;
            background: #ffe0eb;
            color: #d92d6b;
            font-weight: bold;
        }

        .paid {
            color: #16a34a;
            font-weight: bold;
        }

        .btn {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 7px;
            background: #d92d6b;
            color: white;
            text-decoration: none;
        }

        .btn:hover {
            background: #b51f56;
        }

        /* =========================
           PAGINATION
        ========================= */

        .pagination-wrapper {
            margin-top: 25px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .pagination {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .pagination li {
            list-style: none;
        }

        .pagination a,
        .pagination span {
            display: flex;
            align-items: center;
            justify-content: center;

            min-width: 38px;
            height: 38px;

            padding: 0 12px;

            box-sizing: border-box;

            border-radius: 8px;

            text-decoration: none;

            font-size: 14px;
            font-weight: 600;

            border: 1px solid #f3c4d5;

            background: white;
            color: #d92d6b;
        }

        .pagination a:hover {
            background: #ffe0eb;
            color: #b51f56;
        }

        .pagination .active span {
            background: #d92d6b;
            color: white;
            border-color: #d92d6b;
        }

        .pagination .disabled span {
            color: #aaa;
            background: #f8f8f8;
            border-color: #eee;
        }

        /* Quan trọng:
           Không cho SVG của Laravel bị phóng to */
        .pagination svg {
            width: 16px !important;
            height: 16px !important;
        }

        .pagination .relative {
            position: relative;
        }

        .pagination p {
            margin: 0;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media(max-width: 800px) {

            .container {
                width: 95%;
                margin: 20px auto;
            }

            .card {
                padding: 15px;
                overflow-x: auto;
            }

            table {
                font-size: 13px;
                min-width: 800px;
            }

            th,
            td {
                padding: 10px 8px;
            }

            .pagination-wrapper {
                justify-content: center;
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

    <h1>🌸 Quản lý đơn hàng</h1>

    {{-- Thông báo thành công --}}
    @if(session('success'))
        <div style="
            background:#d1fae5;
            color:#047857;
            padding:15px;
            border-radius:10px;
            margin-bottom:20px;
        ">
            {{ session('success') }}
        </div>
    @endif

    {{-- Thông báo lỗi --}}
    @if(session('error'))
        <div style="
            background:#fee2e2;
            color:#b91c1c;
            padding:15px;
            border-radius:10px;
            margin-bottom:20px;
        ">
            {{ session('error') }}
        </div>
    @endif

    <div class="card">

        <table>

            <thead>
                <tr>
                    <th>Mã đơn</th>
                    <th>Khách hàng</th>
                    <th>Ngày giao</th>
                    <th>Tổng tiền</th>
                    <th>Thanh toán</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>

            <tbody>

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

            @forelse($orders as $order)

                <tr>

                    {{-- Mã đơn --}}
                    <td>
                        <strong>
                            {{ $order->order_code }}
                        </strong>
                    </td>

                    {{-- Khách hàng --}}
                    <td>
                        {{ $order->recipient_name }}
                        <br>
                        {{ $order->recipient_phone }}
                    </td>

                    {{-- Ngày giao --}}
                    <td>
                        {{ $order->delivery_date?->format('d/m/Y') }}
                    </td>

                    {{-- Tổng tiền --}}
                    <td>
                        <strong>
                            {{ number_format($order->total, 0, ',', '.') }}đ
                        </strong>
                    </td>

                    {{-- Thanh toán --}}
                    <td>
                        @if($order->payment_status === 'paid')

                            <span class="paid">
                                ✓ Đã thanh toán
                            </span>

                        @else

                            <span>
                                Chưa thanh toán
                            </span>

                        @endif
                    </td>

                    {{-- Trạng thái đơn --}}
                    <td>
                        <span class="status">
                            {{ $statusLabels[$order->order_status] ?? $order->order_status }}
                        </span>
                    </td>

                    {{-- Chi tiết --}}
                    <td>
                        <a
                            href="{{ route('admin.orders.show', $order) }}"
                            class="btn"
                        >
                            Chi tiết
                        </a>
                    </td>

                </tr>

            @empty

                <tr>
                    <td
                        colspan="7"
                        style="text-align:center; padding:30px;"
                    >
                        Chưa có đơn hàng.
                    </td>
                </tr>

            @endforelse

            </tbody>

        </table>

        {{-- =========================
             PAGINATION
        ========================= --}}

        @if($orders->hasPages())

            <div class="pagination-wrapper">
                {{ $orders->links() }}
            </div>

        @endif

    </div>

</div>

</body>
</html>
