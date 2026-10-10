<div class="mb-6 flex flex-wrap gap-3 text-sm font-semibold">
    <a class="rounded-xl bg-white border border-rose-200 px-4 py-2 text-rose-700" href="{{ route('admin.finance.index') }}">Tài chính & Lợi nhuận</a>
    <a class="rounded-xl bg-white border border-rose-200 px-4 py-2 text-rose-700" href="{{ route('admin.finance.orders') }}">Chi phí đơn hàng & Lịch sử</a>
    <a class="rounded-xl bg-white border border-rose-200 px-4 py-2 text-rose-700" href="{{ route('admin.finance.settings') }}">Cấu hình tài chính</a>
    <a class="rounded-xl bg-white border border-rose-200 px-4 py-2 text-rose-700" href="{{ route('admin.finance.expenses') }}">Chi phí vận hành</a>
    <a class="rounded-xl bg-white border border-rose-200 px-4 py-2 text-rose-700" href="{{ route('admin.finance.taxes') }}">Cấu hình & Hồ sơ thuế</a>
</div>
@if(session('success'))<p class="mb-4 rounded-xl bg-green-50 p-4 text-green-800">{{ session('success') }}</p>@endif
@if($errors->any())<ul class="mb-4 rounded-xl bg-red-50 p-4 text-red-700">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
