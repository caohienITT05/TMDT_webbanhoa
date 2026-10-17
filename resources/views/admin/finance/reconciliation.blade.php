@extends('layouts.admin')
@section('title', 'Đối soát tài chính')
@section('content')
@include('admin.finance.nav')
@php
    $money = fn ($value) => $value === null ? 'Thiếu dữ liệu' : number_format($value / 100, 0, ',', '.') . ' đ';
    $displayDate = fn ($value) => \Illuminate\Support\Carbon::parse($value)->format('d/m/Y');
    $needsReview = collect($actual['rows'])->filter(fn ($row) => ! ($row['summary']['financial_data_complete'] ?? false) || (int) ($row['summary']['unallocated_income'] ?? 0) !== 0)->values();
@endphp
<header class="mb-5"><h1 class="admin-page-title">Đối soát tài chính</h1><p class="mt-1 text-sm text-gray-600">{{ $displayDate($from) }} – {{ $displayDate($to) }} · cảnh báo dữ liệu và khoản chưa có căn cứ được giữ riêng.</p></header>

<section class="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
    <article class="rounded-xl border bg-white p-4"><p class="text-sm text-gray-600">Đơn cần kiểm tra chi phí</p><strong class="mt-2 block text-2xl">{{ $actual['totals']['missing_orders'] }}</strong><p class="mt-1 text-xs text-gray-500">Thiếu một hoặc nhiều chi phí nhập tay</p></article>
    <article class="rounded-xl border bg-white p-4"><p class="text-sm text-gray-600">Chi phí đơn dùng cấu hình mặc định</p><strong class="mt-2 block text-2xl">{{ $dataCoverage['orders_with_default_costs'] }}</strong><p class="mt-1 text-xs text-gray-500">Chỉ là ước tính, chưa coi là chi phí thực tế</p></article>
    <article class="rounded-xl border bg-white p-4"><p class="text-sm text-gray-600">Dòng hàng thiếu snapshot giá vốn</p><strong class="mt-2 block text-2xl">{{ max(0, $dataCoverage['items'] - $dataCoverage['items_with_cost']) }} / {{ $dataCoverage['items'] }}</strong><p class="mt-1 text-xs text-gray-500">Không tự thay bằng giá vốn hiện tại</p></article>
    <article class="rounded-xl border border-amber-200 bg-amber-50 p-4"><p class="text-sm text-amber-800">Chênh lệch đơn chưa phân bổ</p><strong class="mt-2 block text-2xl text-amber-950">{{ $money($actual['totals']['unallocated_income']) }}</strong><p class="mt-1 text-xs text-amber-800">Không tự cộng vào lợi nhuận</p></article>
</section>

<section class="mb-5 rounded-xl border bg-white p-4 sm:p-5">
    <div class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="text-lg font-bold">Tình trạng nguồn dữ liệu</h2><p class="mt-1 text-sm text-gray-600">Đơn trong kỳ: {{ $dataCoverage['orders'] }} · hoàn thành: {{ $dataCoverage['completed_orders'] }} · đơn hủy: {{ $dataCoverage['cancelled_orders'] }}.</p></div><a href="{{ route('admin.finance.orders', ['from_date' => $from, 'to_date' => $to]) }}" class="rounded-lg border px-4 py-2 text-sm font-semibold text-rose-700">Mở sổ chi phí đơn</a></div>
    <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg bg-gray-50 p-3"><p class="text-xs text-gray-500">Chi phí vận hành đã nhập</p><p class="mt-1 font-semibold">{{ $dataCoverage['expenses'] }} khoản</p></div>
        <div class="rounded-lg bg-gray-50 p-3"><p class="text-xs text-gray-500">Hồ sơ thuế đã ghi</p><p class="mt-1 font-semibold">{{ $dataCoverage['tax_records'] }}</p></div>
        <div class="rounded-lg bg-gray-50 p-3"><p class="text-xs text-gray-500">Sản phẩm có giá vốn hiện tại</p><p class="mt-1 font-semibold">{{ $dataCoverage['products_with_cost'] }} / {{ $dataCoverage['products'] }}</p></div>
        <div class="rounded-lg bg-gray-50 p-3"><p class="text-xs text-gray-500">Chi phí đơn nhập tay</p><p class="mt-1 font-semibold">{{ $dataCoverage['orders_with_direct_costs'] }} / {{ $dataCoverage['orders'] }} đơn</p></div>
    </div>
</section>

<section class="overflow-hidden rounded-xl border bg-white">
    <div class="border-b p-4"><h2 class="text-lg font-bold">Đơn cần đối soát</h2><p class="mt-1 text-sm text-gray-600">Các khoản lịch sử không được phân bổ lại nếu thiếu hóa đơn hoặc ghi nhận nguồn.</p></div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[850px] text-left text-sm">
            <thead class="bg-gray-50 text-gray-600"><tr><th class="p-3">Đơn hàng</th><th class="p-3">Ngày</th><th class="p-3">Doanh thu</th><th class="p-3">Giá vốn</th><th class="p-3">Chênh lệch chưa phân bổ</th><th class="p-3">Tình trạng</th><th class="p-3"></th></tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($needsReview as $row)
                    <tr>
                        <td class="p-3 font-semibold"><a class="text-rose-700 underline" href="{{ route('admin.finance.order', $row['order']) }}">{{ $row['order']->order_code }}</a></td>
                        <td class="p-3">{{ $row['order']->created_at_local?->format('d/m/Y') }}</td>
                        <td class="p-3">{{ $money($row['summary']['net_product_revenue'] + $row['summary']['shipping_income']) }}</td>
                        <td class="p-3">{{ $money($row['summary']['cogs']) }}</td>
                        <td class="p-3">{{ $money($row['summary']['unallocated_income']) }}</td>
                        <td class="p-3">{{ $row['summary']['data_status'] }}</td>
                        <td class="p-3"><a class="font-semibold text-rose-700 underline" href="{{ route('admin.finance.order', $row['order']) }}">Xem / cập nhật</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-8 text-center text-gray-500">Không có đơn nào cần đối soát theo dữ liệu hiện tại của kỳ này.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
<p class="mt-4 text-xs text-gray-500">Giá vốn hiện tại của sản phẩm không thay thế snapshot của đơn cũ. Chi phí không có chứng từ, khoản giao hàng chưa phân bổ và thuế chưa xác nhận vẫn để trạng thái chưa đủ căn cứ.</p>
@endsection
