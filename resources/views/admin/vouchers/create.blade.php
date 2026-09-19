@extends('layouts.admin')

@section('title', 'Tạo voucher mới')

@section('content')
    <div class="admin-page mx-auto max-w-4xl">
        <div class="admin-panel admin-panel--padded">
            <div class="mb-6 border-b border-rose-50 pb-4">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-rose-500">Khuyến mại</p>
                <h1 class="admin-page-title mt-1">Tạo voucher / Flash Sale</h1>
                <p class="admin-page-subtitle">Thiết lập điều kiện, giá trị và thời gian áp dụng của ưu đãi.</p>
            </div>

            @if($errors->any())
                <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('admin.vouchers.store') }}" method="POST" class="space-y-5">
                @csrf
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label for="code" class="mb-1 block text-sm font-semibold text-gray-700">Mã voucher <span class="text-rose-600">*</span></label>
                        <input id="code" type="text" name="code" value="{{ old('code') }}" required placeholder="VD: BLOOMTRUA" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold uppercase text-gray-800 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                    </div>
                    <div>
                        <label for="name" class="mb-1 block text-sm font-semibold text-gray-700">Tên chương trình <span class="text-rose-600">*</span></label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" required placeholder="VD: Khuyến mại giờ vàng" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                    </div>
                    <div>
                        <label for="type" class="mb-1 block text-sm font-semibold text-gray-700">Loại giảm giá <span class="text-rose-600">*</span></label>
                        <select id="type" name="type" required class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                            <option value="fixed" @selected(old('type') === 'fixed')>Giảm số tiền cố định (VNĐ)</option>
                            <option value="percent" @selected(old('type') === 'percent')>Giảm theo phần trăm (%)</option>
                        </select>
                    </div>
                    <div>
                        <label for="value" class="mb-1 block text-sm font-semibold text-gray-700">Mức giảm <span class="text-rose-600">*</span></label>
                        <input id="value" type="number" step="0.01" name="value" value="{{ old('value') }}" required min="1" placeholder="VD: 50000 hoặc 10" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                    </div>
                    <div>
                        <label for="min_order_amount" class="mb-1 block text-sm font-semibold text-gray-700">Đơn hàng tối thiểu (VNĐ)</label>
                        <input id="min_order_amount" type="number" name="min_order_amount" value="{{ old('min_order_amount', 0) }}" required min="0" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                    </div>
                    <div>
                        <label for="max_discount" class="mb-1 block text-sm font-semibold text-gray-700">Giảm tối đa (VNĐ)</label>
                        <input id="max_discount" type="number" name="max_discount" value="{{ old('max_discount') }}" min="0" placeholder="Để trống nếu không giới hạn" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                    </div>
                    <div>
                        <label for="start_time" class="mb-1 block text-sm font-semibold text-gray-700">Bắt đầu <span class="text-rose-600">*</span></label>
                        <input id="start_time" type="datetime-local" name="start_time" value="{{ old('start_time', now()->format('Y-m-d\\TH:i')) }}" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                    </div>
                    <div>
                        <label for="end_time" class="mb-1 block text-sm font-semibold text-gray-700">Kết thúc <span class="text-rose-600">*</span></label>
                        <input id="end_time" type="datetime-local" name="end_time" value="{{ old('end_time', now()->addDays(3)->format('Y-m-d\\TH:i')) }}" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                    </div>
                    <div>
                        <label for="usage_limit" class="mb-1 block text-sm font-semibold text-gray-700">Số lượng phát hành <span class="text-rose-600">*</span></label>
                        <input id="usage_limit" type="number" name="usage_limit" value="{{ old('usage_limit', 100) }}" required min="1" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                    </div>
                    <label class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-gray-700">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="h-4 w-4 rounded border-gray-300 text-rose-600 focus:ring-rose-500">
                        Kích hoạt sử dụng ngay
                    </label>
                </div>

                <div class="flex flex-wrap justify-end gap-3 border-t border-gray-100 pt-6">
                    <x-admin.button :href="route('admin.vouchers.index')" variant="secondary">Hủy bỏ</x-admin.button>
                    <x-admin.button type="submit" variant="primary">Lưu voucher</x-admin.button>
                </div>
            </form>
        </div>
    </div>
@endsection
