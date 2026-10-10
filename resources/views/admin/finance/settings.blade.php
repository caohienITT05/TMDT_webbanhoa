@extends('layouts.admin')
@section('title', 'Cấu hình tài chính')
@section('content')
@include('admin.finance.nav')
<h1 class="admin-page-title mb-4">Cấu hình tài chính</h1>
<p class="mb-5 text-sm text-gray-600">Các giá trị mặc định dưới đây được sử dụng để tự động bổ sung các khoản chi phí còn thiếu khi đơn hàng chuyển sang trạng thái Hoàn thành. Những chi phí thực tế đã được quản trị viên nhập trước đó sẽ được giữ nguyên và không bị ghi đè.</p>
<form method="POST" action="{{ route('admin.finance.settings.save') }}" class="grid gap-4 rounded-xl border bg-white p-5 md:grid-cols-2">
    @csrf
    <label>Chi phí vận chuyển mặc định (VND)<input required type="number" min="0" step="0.01" name="default_shipping_cost" value="{{ old('default_shipping_cost', $settings->default_shipping_cost) }}" class="block w-full rounded-lg border-gray-300"></label>
    <label>Chi phí đóng gói mặc định (VND)<input required type="number" min="0" step="0.01" name="default_packaging_cost" value="{{ old('default_packaging_cost', $settings->default_packaging_cost) }}" class="block w-full rounded-lg border-gray-300"></label>
    <label>Phí xử lý COD (VND)<input required type="number" min="0" step="0.01" name="cod_fee" value="{{ old('cod_fee', $settings->cod_fee) }}" class="block w-full rounded-lg border-gray-300"></label>
    <label>Tỷ lệ phí PayPal (%)<input required type="number" min="0" max="100" step="0.0001" name="paypal_fee_rate" value="{{ old('paypal_fee_rate', $settings->paypal_fee_rate) }}" class="block w-full rounded-lg border-gray-300"></label>
    <label>Phí PayPal cố định (VND)<input required type="number" min="0" step="0.01" name="paypal_fixed_fee" value="{{ old('paypal_fixed_fee', $settings->paypal_fixed_fee) }}" class="block w-full rounded-lg border-gray-300"></label>
    <label>Đơn vị tiền tệ<select name="currency" class="block w-full rounded-lg border-gray-300"><option value="VND" @selected(old('currency', $settings->currency) === 'VND')>VND</option><option value="USD" @selected(old('currency', $settings->currency) === 'USD')>USD</option></select></label>
    <div class="md:col-span-2"><button class="rounded-lg bg-rose-600 px-5 py-2 text-white">Lưu cấu hình</button></div>
</form>
@endsection
