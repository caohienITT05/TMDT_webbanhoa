@extends('layouts.admin')

@section('title', 'Quản lý đánh giá')

@section('content')
<div class="mx-auto max-w-7xl space-y-5 px-1 sm:px-0">
    <header class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-sm font-semibold text-rose-700">Nội dung khách hàng</p>
            <h1 class="mt-1 text-2xl font-bold text-gray-900">Quản lý đánh giá sản phẩm</h1>
            <p class="mt-1 text-sm text-gray-600">Đánh giá đạt điều kiện được đăng tự động. Chỉ bài cần xem xét hoặc bị báo cáo cần Admin xử lý.</p>
        </div>
        <a href="{{ route('admin.products.index') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700">Về sản phẩm</a>
    </header>

    @if(session('success'))<p role="status" class="rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('success') }}</p>@endif
    @if($errors->any())<ul role="alert" class="rounded-lg bg-red-50 p-3 text-sm text-red-800">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif

    <nav aria-label="Lọc đánh giá" class="grid grid-cols-2 gap-2 sm:grid-cols-5">
        @foreach(['all' => 'Tất cả', 'published' => 'Đã công bố', 'needs-review' => 'Cần xem xét', 'reported' => 'Bị báo cáo', 'hidden' => 'Đã ẩn'] as $key => $label)
            <a href="{{ route('admin.reviews.index', ['status' => $key]) }}" @class([
                'flex items-center justify-between rounded-xl border px-3 py-3 text-sm font-semibold',
                'border-rose-300 bg-rose-50 text-rose-800' => $status === $key,
                'border-gray-200 bg-white text-gray-700 hover:bg-gray-50' => $status !== $key,
            ])>
                <span>{{ $label }}</span><span class="ml-2 rounded-full bg-white/80 px-2 py-0.5 text-xs">{{ $statusCounts[$key] }}</span>
            </a>
        @endforeach
    </nav>

    @unless($historyAvailable)
        <p class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">Cần áp dụng migration bổ sung để bật bộ đếm đánh giá tiêu cực và nhật ký kiểm duyệt.</p>
    @endunless
    @unless($reportsAvailable)
        <p class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">Chức năng báo cáo đánh giá cần migration bổ sung; các đánh giá hiện có vẫn được giữ nguyên.</p>
    @endunless

    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-600">
                    <tr><th class="px-4 py-3">Đánh giá</th><th class="px-4 py-3">Sản phẩm / đơn</th><th class="px-4 py-3">Lý do cần xem</th><th class="px-4 py-3">Xử lý</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($reviews as $review)
                        <tr class="align-top">
                            <td class="max-w-md px-4 py-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <strong class="text-gray-900">{{ $review->reviewer_name }}</strong>
                                    <span class="text-xs text-gray-500">{{ $review->created_at?->format('d/m/Y H:i') }}</span>
                                </div>
                                <p class="mt-1 text-amber-600" aria-label="{{ $review->rating }} trên 5 sao">{{ str_repeat('★', (int) $review->rating) }}{{ str_repeat('☆', 5 - (int) $review->rating) }}</p>
                                @if($review->title)<h2 class="mt-2 font-semibold text-gray-800">{{ $review->title }}</h2>@endif
                                <p class="mt-1 whitespace-pre-line break-words text-gray-700">{{ $review->content }}</p>
                                @if($review->image_path)<a href="{{ asset('storage/'.$review->image_path) }}" target="_blank" rel="noopener"><img src="{{ asset('storage/'.$review->image_path) }}" alt="Ảnh khách gửi kèm đánh giá" loading="lazy" class="mt-2 max-h-36 rounded-lg border object-contain"></a>@endif
                            </td>
                            <td class="px-4 py-4">
                                <p class="font-semibold text-gray-900">{{ $review->product?->name ?? 'Sản phẩm đã lưu trữ' }}</p>
                                <p class="mt-1 text-xs text-gray-500">Đơn {{ $review->order?->order_code ?? 'cũ/không còn liên kết' }}</p>
                                <p class="mt-1 text-xs text-gray-500">{{ $review->is_verified_purchase ? 'Đã mua, đã giao' : 'Chưa xác minh đơn mua' }}</p>
                                <p class="mt-2 text-xs text-gray-600">Lịch sử 1–2 sao của tài khoản: <strong>{{ $review->lifetime_negative_count }}</strong></p>
                            </td>
                            <td class="max-w-sm px-4 py-4">
                                @if($review->status === 'pending' && (int) $review->rating <= 2 && $review->lifetime_negative_count > 10)
                                    <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-900">Vượt ngưỡng: đánh giá tiêu cực thứ {{ $review->lifetime_negative_count }}</span>
                                @elseif($review->status === 'pending')
                                    <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-900">Chờ xem xét thủ công</span>
                                @elseif($review->status === 'hidden')
                                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">Đã ẩn</span>
                                @elseif($review->status === 'rejected')
                                    <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-800">Đã từ chối</span>
                                @else
                                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">Đã công bố</span>
                                @endif
                                @if(($moderationHistory[$review->id] ?? collect())->isNotEmpty())
                                    <details class="mt-2 text-xs text-gray-600">
                                        <summary class="cursor-pointer">Lịch sử xử lý ({{ $moderationHistory[$review->id]->count() }})</summary>
                                        <ul class="mt-1 space-y-1">
                                            @foreach($moderationHistory[$review->id] as $event)
                                                <li>{{ $event->created_at }} · {{ $event->from_status }} → {{ $event->to_status }}@if($event->note) · {{ $event->note }}@endif</li>
                                            @endforeach
                                        </ul>
                                    </details>
                                @endif
                                @if($reportsAvailable && $review->reports?->isNotEmpty())
                                    <ul class="mt-2 space-y-1 text-xs text-red-800">
                                        @foreach($review->reports as $report)
                                            <li>Báo cáo: {{ ['spam' => 'Spam', 'abuse' => 'Ngôn từ vi phạm', 'irrelevant' => 'Không liên quan', 'other' => 'Lý do khác'][$report->reason] ?? $report->reason }}@if($report->details) — {{ $report->details }}@endif</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <form method="POST" action="{{ route('admin.reviews.update', $review) }}" class="relative space-y-2">
                                    @csrf @method('PUT')
                                    <label class="sr-only" for="review-status-{{ $review->id }}">Thao tác đánh giá</label>
                                    <select id="review-status-{{ $review->id }}" name="status" required class="w-full rounded-lg border-gray-300 text-sm">
                                        <option value="">Chọn thao tác…</option>
                                        <option value="published">Công bố</option>
                                        <option value="hidden">Ẩn đánh giá</option>
                                        <option value="rejected">Từ chối vi phạm</option>
                                    </select>
                                    <input name="moderation_note" maxlength="500" class="w-full rounded-lg border-gray-300 text-xs" placeholder="Ghi chú lý do xử lý">
                                    <textarea name="store_response" maxlength="2000" rows="2" class="w-full rounded-lg border-gray-300 text-xs" placeholder="Phản hồi của cửa hàng (tùy chọn)">{{ old('store_response', $review->store_response) }}</textarea>
                                    <button class="w-full rounded-lg bg-gray-900 px-3 py-2 text-xs font-semibold text-white">Lưu xử lý</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-12 text-center text-gray-500">Không có đánh giá trong mục này.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-100 p-4">{{ $reviews->links() }}</div>
    </section>
</div>
@endsection
