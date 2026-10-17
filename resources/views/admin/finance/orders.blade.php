@extends('layouts.admin')
@section('content')
@include('admin.finance.nav')
@php $statuses = ['pending'=>'Chờ xử lý','confirmed'=>'Đã xác nhận','processing'=>'Đang xử lý','preparing'=>'Đang chuẩn bị','shipping'=>'Đang giao','delivered'=>'Đã giao','completed'=>'Hoàn thành','cancelled'=>'Đã hủy']; @endphp
<h1 class="admin-page-title mb-4">Chi phí đơn hàng & Lịch sử</h1>
<form class="flex gap-3 mb-4"><input name="search" value="{{ request('search') }}" placeholder="Mã đơn hàng" class="rounded-lg border-gray-300"><button class="rounded-lg bg-rose-600 px-4 py-2 text-white">Tìm</button></form>
<div class="rounded-xl bg-white border p-5 overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr><th>Đơn hàng</th><th>Ngày tạo</th><th>Trạng thái</th><th>Chi phí</th></tr></thead><tbody>
@foreach($orders as $order)<tr class="border-t"><td class="py-3">{{ $order->order_code }}</td><td>{{ $order->created_at_local->format('d/m/Y') }}</td><td>{{ $statuses[strtolower($order->order_status)] ?? $order->order_status }}</td><td><a class="text-rose-600" href="{{ route('admin.finance.order', $order) }}">Nhập / xem chi phí thực tế</a></td></tr>@endforeach
</tbody></table>{{ $orders->links() }}</div>
@endsection
