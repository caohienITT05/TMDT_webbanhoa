@extends('layouts.admin')
@section('content')
@include('admin.finance.nav')
@php
    $types = ['advertising'=>'Quảng cáo','salary'=>'Lương','rent'=>'Thuê mặt bằng','electricity'=>'Điện','water'=>'Nước','internet'=>'Internet','software'=>'Phần mềm','product_loss'=>'Hao hụt hàng','office'=>'Văn phòng','maintenance'=>'Bảo trì','other'=>'Khác'];
    $displayDate = fn ($value) => \Illuminate\Support\Carbon::parse($value)->format('d/m/Y');
@endphp
<h1 class="admin-page-title mb-4">Chi phí vận hành</h1>
<p class="mb-4 text-sm">Chỉ ghi chi phí vận hành chung. Không nhập lại ship, đóng gói, phí thanh toán đã ghi ở chi phí đơn hàng. Hao hụt chỉ ghi phần chưa nằm trong giá vốn bán hàng.</p>
<form method="POST" action="{{ route('admin.finance.expenses.save') }}" class="bg-white border rounded-xl p-5 grid md:grid-cols-2 gap-4 mb-6">
@csrf
<input type="hidden" name="id" value="{{ old('id', $editing?->id) }}">
<h2 class="md:col-span-2 text-lg font-bold">{{ $editing ? 'Sửa chi phí' : 'Thêm chi phí' }}</h2>
<label>Loại<select name="expense_type" class="block w-full rounded-lg border-gray-300">@foreach($types as $key=>$label)<option value="{{ $key }}" @selected(old('expense_type', $editing?->expense_type) === $key)>{{ $label }}</option>@endforeach</select></label>
<label>Ngày chi<input type="date" required name="expense_date" value="{{ old('expense_date', $editing?->expense_date ?? now()->toDateString()) }}" class="block w-full rounded-lg border-gray-300"></label>
<label>Nội dung<input required name="description" maxlength="255" value="{{ old('description', $editing?->description) }}" class="block w-full rounded-lg border-gray-300"></label>
<label>Số tiền (VNĐ)<input required type="number" min="0" step="0.01" name="amount" value="{{ old('amount', $editing?->amount) }}" class="block w-full rounded-lg border-gray-300"></label>
<label class="md:col-span-2">Ghi chú<textarea name="note" class="block w-full rounded-lg border-gray-300">{{ old('note', $editing?->note) }}</textarea></label>
<div><button class="rounded-lg bg-rose-600 px-5 py-2 text-white">Lưu chi phí</button></div>
</form>
<form class="flex flex-wrap gap-3 mb-4"><label>Từ ngày<input class="block rounded-lg border-gray-300" type="date" name="from_date" value="{{ request('from_date') }}"></label><label>Đến ngày<input class="block rounded-lg border-gray-300" type="date" name="to_date" value="{{ request('to_date') }}"></label><label>Loại<select name="expense_type" class="block rounded-lg border-gray-300"><option value="">Tất cả</option>@foreach($types as $key=>$label)<option value="{{ $key }}" @selected(request('expense_type') === $key)>{{ $label }}</option>@endforeach</select></label><button class="self-end rounded-lg bg-rose-600 px-4 py-2 text-white">Lọc</button></form>
<div class="bg-white border rounded-xl p-5 overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr><th>Ngày</th><th>Loại</th><th>Nội dung</th><th>Số tiền</th><th></th></tr></thead><tbody>@forelse($expenses as $expense)<tr class="border-t"><td class="py-3">{{ $displayDate($expense->expense_date) }}</td><td>{{ $types[$expense->expense_type] }}</td><td>{{ $expense->description }}</td><td>{{ number_format($expense->amount, 0, ',', '.') }} đ</td><td><a class="text-rose-600" href="{{ route('admin.finance.expenses', ['edit'=>$expense->id]) }}">Sửa</a></td></tr>@empty<tr><td class="py-4" colspan="5">Chưa có chi phí.</td></tr>@endforelse</tbody></table>{{ $expenses->links() }}</div>
@endsection
