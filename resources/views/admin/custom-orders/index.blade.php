@extends('layouts.admin')

@section('title', 'Yêu cầu đặt hoa')

@section('content')
    <div class="admin-page space-y-5">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-rose-500">Vận hành</p><h1 class="admin-page-title mt-1">Yêu cầu đặt hoa</h1><p class="admin-page-subtitle">Tiếp nhận yêu cầu riêng, liên hệ khách và chỉ tạo đơn sau khi đã chốt.</p></div>
            <form method="GET" class="flex items-center gap-2"><label class="sr-only" for="status">Lọc trạng thái</label><select id="status" name="status" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700"><option value="">Tất cả trạng thái</option><option value="pending" @selected(request('status') === 'pending')>Chờ xử lý</option><option value="contacted" @selected(request('status') === 'contacted')>Đã liên hệ</option><option value="accepted" @selected(request('status') === 'accepted')>Shop nhận yêu cầu</option><option value="rejected" @selected(request('status') === 'rejected')>Shop từ chối</option><option value="converted" @selected(request('status') === 'converted')>Đã tạo đơn</option></select><x-admin.button type="submit" variant="secondary" size="sm">Lọc</x-admin.button></form>
        </div>

        <div class="admin-panel overflow-hidden">
            <div class="overflow-x-auto"><table class="w-full min-w-[820px] text-left text-sm text-gray-600"><thead class="border-b border-rose-100 bg-rose-50/70 text-[11px] font-bold uppercase tracking-wide text-rose-900"><tr><th class="px-5 py-3.5">Mã yêu cầu</th><th class="px-5 py-3.5">Khách</th><th class="px-5 py-3.5">Dịp / loại hoa</th><th class="px-5 py-3.5">Ngày gửi</th><th class="px-5 py-3.5">Trạng thái</th><th class="px-5 py-3.5 text-right">Thao tác</th></tr></thead><tbody class="divide-y divide-rose-50">
                @forelse ($customOrderRequests as $customOrderRequest)
                    <tr class="transition-colors hover:bg-rose-50/35"><td class="px-5 py-4 font-semibold text-gray-900">{{ $customOrderRequest->request_code }}</td><td class="px-5 py-4"><p class="font-medium text-gray-800">{{ $customOrderRequest->customer_name }}</p><p class="mt-0.5 text-xs text-gray-400">{{ $customOrderRequest->phone }} · {{ $customOrderRequest->user_id ? 'Thành viên' : 'Khách vãng lai' }}</p></td><td class="px-5 py-4"><p class="font-medium text-gray-800">{{ $customOrderRequest->occasion ?: '—' }}</p><p class="mt-0.5 text-xs text-gray-400">{{ $customOrderRequest->flower_type ?: 'Chưa nêu loại hoa' }}</p></td><td class="px-5 py-4 text-sm">{{ $customOrderRequest->created_at->format('d/m/Y H:i') }}</td><td class="px-5 py-4"><x-admin.status-badge :status="$customOrderRequest->status" type="custom-order" /></td><td class="px-5 py-4 text-right"><x-admin.button :href="route('admin.custom-orders.show', $customOrderRequest)" variant="detail" size="sm">Xem yêu cầu</x-admin.button></td></tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-12 text-center text-sm text-gray-400">Chưa có yêu cầu đặt hoa.</td></tr>
                @endforelse
            </tbody></table></div>
            <div class="border-t border-rose-50 px-5 py-4"><x-admin.pagination :paginator="$customOrderRequests" item-label="yêu cầu" /></div>
        </div>
    </div>
@endsection
