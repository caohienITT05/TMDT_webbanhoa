<nav aria-label="Điều hướng tài chính" class="mb-5 flex flex-wrap gap-2 text-sm font-semibold">
    @foreach([
        'admin.finance.index' => ['Tổng quan', route('admin.finance.index')],
        'admin.finance.revenue' => ['Doanh thu & Lợi nhuận', route('admin.finance.revenue')],
        'admin.finance.expenses' => ['Chi phí', route('admin.finance.expenses')],
        'admin.finance.taxes' => ['Thuế', route('admin.finance.taxes')],
        'admin.finance.reconciliation' => ['Đối soát', route('admin.finance.reconciliation')],
    ] as $routeName => [$label, $url])
        <a href="{{ $url }}" @class([
            'rounded-lg border px-3 py-2',
            'border-rose-300 bg-rose-50 text-rose-800' => request()->routeIs($routeName),
            'border-gray-200 bg-white text-gray-700 hover:bg-gray-50' => ! request()->routeIs($routeName),
        ])>{{ $label }}</a>
    @endforeach
</nav>
<details class="mb-5 rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm">
    <summary class="cursor-pointer font-semibold text-gray-700">Sổ chi tiết và cấu hình</summary>
    <div class="mt-3 flex flex-wrap gap-2">
        <a class="rounded-lg border px-3 py-2 text-gray-700" href="{{ route('admin.finance.orders') }}">Chi phí đơn hàng & lịch sử</a>
        <a class="rounded-lg border px-3 py-2 text-gray-700" href="{{ route('admin.finance.settings') }}">Cấu hình tài chính</a>
        <a class="rounded-lg border px-3 py-2 text-gray-700" href="{{ route('admin.finance.vat-products') }}">Cấu hình VAT sản phẩm</a>
    </div>
</details>
@if(session('success'))<p class="mb-4 rounded-xl bg-green-50 p-4 text-green-800">{{ session('success') }}</p>@endif
@if($errors->any())<ul class="mb-4 rounded-xl bg-red-50 p-4 text-red-700">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
