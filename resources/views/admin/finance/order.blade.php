@extends('layouts.admin')
@section('content')
@include('admin.finance.nav')
<h1 class="admin-page-title mb-4">Chi phí thực tế · {{ $order->order_code }}</h1>
<p class="mb-4">{{ $summary['data_status'] }} · <a class="text-rose-600" href="{{ route('admin.orders.show', $order) }}">Xem đơn hàng</a></p>
@if($cost?->financial_finalized_at)<p class="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-800">Đã tự động hoàn tất tài chính lúc {{ \Illuminate\Support\Carbon::parse($cost->financial_finalized_at)->format('d/m/Y H:i') }} · nguồn {{ $cost->source === 'manual' ? 'Quản trị viên nhập' : 'cấu hình hệ thống' }}.</p>@endif
<form method="POST" action="{{ route('admin.finance.order.save', $order) }}" class="rounded-xl bg-white border p-6 space-y-5">
@csrf @method('PUT')
<p class="text-sm text-gray-600">Nhập số tiền thực tế theo chứng từ (VNĐ). Để trống nếu chưa biết; nhập 0 nếu đã xác nhận không phát sinh. Giá vốn đã chụp tại thời điểm mua được giữ nguyên. Chỉ bổ sung giá vốn còn thiếu bằng chứng từ lịch sử.</p>
<div class="overflow-x-auto"><table class="w-full text-left"><thead><tr><th>Sản phẩm</th><th>Số lượng</th><th>Giá vốn / đơn vị</th></tr></thead><tbody>
@foreach($order->items as $item)<tr class="border-t"><td class="py-3">{{ $item->product_name }}</td><td>{{ $item->quantity }}</td><td>
@if($item->cost_price_snapshot === null)<input aria-label="Giá vốn {{ $item->product_name }}" type="number" min="0" step="0.01" name="snapshots[{{ $item->id }}]" value="{{ old('snapshots.'.$item->id) }}" class="rounded-lg border-gray-300">@else{{ number_format($item->cost_price_snapshot, 0, ',', '.') }} đ@endif
</td></tr>@endforeach
</tbody></table></div>
<div class="grid md:grid-cols-2 gap-4">
@foreach(['shipping_cost_actual'=>'Chi phí vận chuyển thực tế', 'packaging_cost_actual'=>'Chi phí đóng gói thực tế', 'payment_gateway_fee_actual'=>'Phí thanh toán thực tế', 'other_direct_cost'=>'Chi phí trực tiếp khác'] as $field=>$label)
<label>{{ $label }} (VNĐ)<input type="number" min="0" step="0.01" name="{{ $field }}" value="{{ old($field, $cost?->$field) }}" class="block w-full rounded-lg border-gray-300"></label>
@endforeach
</div>
<label class="block">Ghi chú / chứng từ<textarea name="note" class="block w-full rounded-lg border-gray-300">{{ old('note', $cost?->note) }}</textarea></label>
<p>Phí giao hàng khách trả: {{ number_format($order->shipping_fee, 0, ',', '.') }} đ</p>
<p>Lợi nhuận trực tiếp: <strong>{{ $summary['order_profit'] === null ? 'Thiếu dữ liệu chi phí' : number_format($summary['order_profit'] / 100, 0, ',', '.').' đ' }}</strong></p>
<p class="text-sm">Chỉ đơn hoàn thành được đưa vào số thực hiện. Đơn hủy chỉ ghi nhận chi phí trực tiếp đã nhập.</p>
<button class="rounded-lg bg-rose-600 px-5 py-2 text-white">Lưu chi phí thực tế</button>
</form>
@endsection
