@extends('layouts.admin')

@section('title', 'Khuyến mại & Voucher')

@section('content')
    <div class="admin-page space-y-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-rose-500">Khuyến mại</p>
                <h1 class="admin-page-title mt-1">Mã giảm giá & Flash Sale</h1>
                <p class="admin-page-subtitle">Thiết lập ưu đãi và theo dõi thời gian áp dụng.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <x-admin.button :href="route('admin.dashboard')" variant="secondary">Quay lại Dashboard</x-admin.button>
                <x-admin.button :href="route('admin.vouchers.create')" variant="primary">
                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10 4v12M4 10h12" stroke-linecap="round" /></svg>
                    Tạo voucher mới
                </x-admin.button>
            </div>
        </div>

        @if(session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="status">
                {{ session('success') }}
            </div>
        @endif

        <div class="admin-panel overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[980px] text-left text-sm text-gray-600">
                    <thead class="border-b border-rose-100 bg-rose-50/70 text-[11px] font-bold uppercase tracking-wide text-rose-900">
                        <tr>
                            <th class="px-5 py-3.5">Mã code</th>
                            <th class="px-5 py-3.5">Chương trình</th>
                            <th class="px-5 py-3.5">Mức giảm</th>
                            <th class="px-5 py-3.5">Đơn tối thiểu</th>
                            <th class="px-5 py-3.5">Thời gian áp dụng</th>
                            <th class="px-5 py-3.5">Lượt dùng</th>
                            <th class="px-5 py-3.5">Trạng thái</th>
                            <th class="px-5 py-3.5 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-rose-50">
                        @forelse($vouchers as $voucher)
                            @php
                                $now = now();
                                $isOngoing = $now->between($voucher->start_time, $voucher->end_time);
                                $isExpired = $now->gt($voucher->end_time);
                                [$voucherLabel, $voucherTone] = !$voucher->is_active
                                    ? ['Tạm tắt', 'neutral']
                                    : ($isExpired ? ['Đã hết hạn', 'danger'] : ($isOngoing ? ['Đang diễn ra', 'success'] : ['Sắp diễn ra', 'warning']));
                            @endphp
                            <tr class="transition-colors hover:bg-rose-50/35">
                                <td class="px-5 py-4"><span class="font-mono text-xs font-bold text-rose-700">{{ $voucher->code }}</span></td>
                                <td class="px-5 py-4 font-medium text-gray-900">{{ $voucher->name }}</td>
                                <td class="px-5 py-4 font-semibold text-gray-900">
                                    {{ $voucher->type === 'percent' ? $voucher->value . '%' : number_format($voucher->value) . ' đ' }}
                                    @if($voucher->max_discount)
                                        <p class="mt-0.5 text-xs font-normal text-gray-400">Tối đa {{ number_format($voucher->max_discount) }} đ</p>
                                    @endif
                                </td>
                                <td class="px-5 py-4">{{ number_format($voucher->min_order_amount) }} đ</td>
                                <td class="px-5 py-4 text-xs leading-5 text-gray-600">
                                    <p>Từ {{ $voucher->start_time->format('H:i d/m/Y') }}</p>
                                    <p>Đến {{ $voucher->end_time->format('H:i d/m/Y') }}</p>
                                </td>
                                <td class="px-5 py-4">{{ $voucher->used_count }} / {{ $voucher->usage_limit }}</td>
                                <td class="px-5 py-4"><span class="admin-badge admin-badge--{{ $voucherTone }}">{{ $voucherLabel }}</span></td>
                                <td class="px-5 py-4 text-right">
                                    <div class="admin-table-actions">
                                        <x-admin.button :href="route('admin.vouchers.edit', $voucher)" variant="detail" size="sm">Sửa</x-admin.button>
                                        <form action="{{ route('admin.vouchers.destroy', $voucher) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa voucher này?')">
                                            @csrf
                                            @method('DELETE')
                                            <x-admin.button type="submit" variant="danger" size="sm">Xóa</x-admin.button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-12 text-center text-sm text-gray-400">Chưa có mã khuyến mại nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-rose-50 px-5 py-4">
                <x-admin.pagination :paginator="$vouchers" item-label="voucher" />
            </div>
        </div>
    </div>
@endsection
