@extends('layouts.admin')
@section('title', 'Cấu hình VAT sản phẩm')
@section('content')
@include('admin.finance.nav')
<h1 class="admin-page-title mb-2">Cấu hình VAT sản phẩm</h1>
<p class="mb-5 text-sm text-gray-600">Tạo quy tắc cho toàn cửa hàng, một danh mục hoặc nhiều sản phẩm. Mỗi lần lưu tạo một mục lịch sử mới; ngày hiệu lực mặc định từ hôm nay để không tính lại VAT của đơn cũ.</p>
@if(!$rulesAvailable)<p class="mb-5 rounded-lg bg-amber-50 p-4 text-sm text-amber-900">Chưa cài đặt bảng lịch sử quy tắc VAT. Cấu hình hiện tạm thời chỉ xem được; hãy chạy migration đã được kiểm thử trước khi lưu.</p>@endif

<section class="mb-6 rounded-xl border bg-white p-5 shadow-sm">
    <h2 class="text-lg font-bold">Thêm quy tắc VAT</h2>
    <form method="POST" action="{{ route('admin.finance.vat-products.save') }}" class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @csrf
        <label class="text-sm font-medium">Phạm vi áp dụng
            <select name="scope" id="vat-scope" required class="mt-1 block w-full rounded-lg border-gray-300">
                <option value="store" @selected(old('scope') === 'store')>Toàn cửa hàng</option>
                <option value="category" @selected(old('scope') === 'category')>Một danh mục</option>
                <option value="products" @selected(old('scope') === 'products')>Nhiều sản phẩm được chọn</option>
            </select>
        </label>
        <label id="vat-category-field" class="hidden text-sm font-medium">Danh mục
            <select name="category_id" class="mt-1 block w-full rounded-lg border-gray-300">
                <option value="">Chọn danh mục</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </label>
        <label id="vat-products-field" class="hidden text-sm font-medium md:col-span-2">Sản phẩm
            <select name="product_ids[]" multiple size="7" class="mt-1 block w-full rounded-lg border-gray-300">
                @foreach($products as $product)
                    <option value="{{ $product->id }}" data-category="{{ $product->category_id }}" data-price="{{ $product->price }}" data-sale-price="{{ $product->sale_price }}" @selected(in_array((string) $product->id, array_map('strval', old('product_ids', [])), true))>{{ $product->name }}{{ $product->is_active ? '' : ' · Đang ẩn' }}</option>
                @endforeach
            </select>
            <span class="mt-1 block text-xs text-gray-500">Giữ Ctrl (Windows) hoặc Command (Mac) để chọn nhiều sản phẩm. Tối đa 200 sản phẩm mỗi quy tắc.</span>
        </label>
        <label class="text-sm font-medium">Phân loại thuế suất
            <select name="vat_category" id="vat-category" required class="mt-1 block w-full rounded-lg border-gray-300">
                @foreach($vatCategories as $key => $vatCategory)
                    <option value="{{ $key }}" data-rate="{{ $vatCategory['rate'] }}" @selected(old('vat_category') === $key)>{{ $vatCategory['label'] }}{{ $vatCategory['rate'] !== null ? ' · '.$vatCategory['rate'].'%' : '' }}</option>
                @endforeach
            </select>
        </label>
        <label id="vat-custom-rate-field" class="hidden text-sm font-medium">Thuế suất tùy chỉnh (%)
            <input name="vat_rate" type="number" min="0" max="100" step="0.0001" value="{{ old('vat_rate') }}" class="mt-1 block w-full rounded-lg border-gray-300">
        </label>
        <label class="text-sm font-medium">Giá bán
            <select name="price_includes_vat" required class="mt-1 block w-full rounded-lg border-gray-300">
                <option value="1" @selected(old('price_includes_vat', '1') === '1')>Đã gồm VAT</option>
                <option value="0" @selected(old('price_includes_vat') === '0')>Chưa gồm VAT</option>
            </select>
        </label>
        <label class="text-sm font-medium">Hiệu lực từ
            <input name="effective_from" type="date" min="{{ $today }}" value="{{ old('effective_from', $today) }}" required class="mt-1 block w-full rounded-lg border-gray-300">
        </label>
        <label class="text-sm font-medium">Hiệu lực đến (để trống nếu chưa xác định)
            <input name="effective_to" type="date" min="{{ old('effective_from', $today) }}" value="{{ old('effective_to') }}" class="mt-1 block w-full rounded-lg border-gray-300">
        </label>
        <label class="text-sm font-medium md:col-span-2 xl:col-span-3">Căn cứ / ghi chú đối soát
            <textarea name="note" rows="2" maxlength="5000" class="mt-1 block w-full rounded-lg border-gray-300" placeholder="Ghi căn cứ phân loại hoặc văn bản áp dụng">{{ old('note') }}</textarea>
        </label>
        <div class="rounded-lg border border-blue-100 bg-blue-50 p-4 text-sm text-blue-950 md:col-span-2 xl:col-span-3" aria-live="polite">
            <strong>Xem trước ảnh hưởng của quy tắc</strong>
            <p class="mt-1"><span id="vat-preview-count">0</span> sản phẩm trong phạm vi · tổng giá bán hiện tại (mỗi sản phẩm 1 đơn vị): <span id="vat-preview-base">0 đ</span> · VAT minh họa: <span id="vat-preview-tax">0 đ</span>.</p>
            <p class="mt-1 text-xs">Chỉ xem trước theo giá hiện tại và thuế suất bạn chọn. Không sửa đơn cũ, không phải số thuế đã kê khai hoặc căn cứ tự phân loại hàng hóa.</p>
        </div>
        <div class="md:col-span-2 xl:col-span-3"><button @disabled(!$rulesAvailable) class="rounded-lg bg-rose-600 px-5 py-2 font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50">Thêm quy tắc mới</button></div>
    </form>
</section>

<section class="mb-6 rounded-xl border bg-white p-5" aria-label="Đối soát VAT đơn hàng cũ">
    <h2 class="text-lg font-bold">Đối soát VAT đơn hàng cũ</h2>
    <p class="mt-1 text-sm text-gray-600">{{ $vatHistoryOrderCount }} đơn hoàn thành từ {{ $vatHistoryFrom ? \Illuminate\Support\Carbon::parse($vatHistoryFrom)->format('d/m/Y') : 'khi có đơn đầu tiên' }} đến hôm nay. Quy tắc mới có ngày hiệu lực từ hôm nay không làm thay đổi tổng tiền hay VAT của đơn trước đó.</p>
    <div class="mt-3 grid gap-3 sm:grid-cols-3">
        <div class="rounded-lg bg-gray-50 p-3"><p class="text-xs text-gray-500">Cơ sở có thuế suất dương</p><strong>{{ $vatHistory['taxable_base'] === null ? 'Thiếu hồ sơ thuế' : number_format($vatHistory['taxable_base'] / 100, 0, ',', '.').' đ' }}</strong></div>
        <div class="rounded-lg bg-amber-50 p-3"><p class="text-xs text-amber-800">Cơ sở chưa đủ phân loại</p><strong>{{ $vatHistory['unclassified_base'] === null ? 'Thiếu hồ sơ thuế' : number_format($vatHistory['unclassified_base'] / 100, 0, ',', '.').' đ' }}</strong></div>
        <div class="rounded-lg bg-blue-50 p-3"><p class="text-xs text-blue-800">VAT đầu ra tham khảo</p><strong>{{ $vatHistory['tax'] === null ? 'Chưa thể xác định' : number_format($vatHistory['tax'] / 100, 0, ',', '.').' đ' }}</strong></div>
    </div>
    <p class="mt-2 text-xs text-gray-500">Báo cáo đọc dữ liệu và quy tắc có hiệu lực tại ngày đặt đơn. Phần chưa phân loại, phí dịch vụ chưa tách hoặc chênh lệch lịch sử không được tự gán thuế suất.</p>
</section>

<section class="rounded-xl border bg-white p-5 shadow-sm">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div><h2 class="text-lg font-bold">Lịch sử quy tắc VAT</h2><p class="mt-1 text-sm text-gray-500">Quy tắc đã lưu không bị ghi đè. Quy tắc mới hơn cùng phạm vi chỉ áp dụng từ ngày hiệu lực ghi trong lịch sử.</p></div>
        <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800">{{ $rules->count() }} quy tắc</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[1000px] text-left text-sm">
            <thead><tr class="border-b text-gray-500"><th class="p-2">Phạm vi</th><th>Đối tượng</th><th>Thuế suất</th><th>Giá bán</th><th>Hiệu lực</th><th>Ghi chú / người tạo</th></tr></thead>
            <tbody>
            @forelse($rules as $rule)
                @php
                    $scopeLabel = ['store' => 'Toàn cửa hàng', 'category' => 'Danh mục', 'products' => 'Nhiều sản phẩm'][$rule->scope] ?? $rule->scope;
                    $rate = $rule->vat_category === 'custom' ? $rule->vat_rate : ($vatCategories[$rule->vat_category]['rate'] ?? $rule->vat_rate);
                @endphp
                <tr class="border-b align-top">
                    <td class="p-2 font-semibold">{{ $scopeLabel }}</td>
                    <td>{{ $rule->scope === 'store' ? 'Tất cả sản phẩm chưa có quy tắc cụ thể' : ($rule->scope === 'category' ? ($rule->category_name ?? $rule->category_name_snapshot ?? 'Danh mục đã xóa') : ($rule->product_names ?: 'Không còn sản phẩm')) }}</td>
                    <td>{{ $vatCategories[$rule->vat_category]['label'] ?? $rule->vat_category }} · {{ number_format((float) $rate, 2, ',', '.') }}%</td>
                    <td>{{ $rule->price_includes_vat ? 'Đã gồm VAT' : 'Chưa gồm VAT' }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($rule->effective_from)->format('d/m/Y') }} – {{ $rule->effective_to ? \Illuminate\Support\Carbon::parse($rule->effective_to)->format('d/m/Y') : 'Đến khi có quy tắc mới' }}</td>
                    <td>{{ $rule->note ?: '—' }}<span class="mt-1 block text-xs text-gray-500">{{ $rule->creator_name ?: 'Tài khoản đã xóa' }} · {{ \Illuminate\Support\Carbon::parse($rule->created_at)->format('d/m/Y H:i') }}</span></td>
                </tr>
            @empty
                <tr><td colspan="6" class="p-6 text-center text-gray-500">Chưa có quy tắc VAT theo cửa hàng, danh mục hoặc nhiều sản phẩm. Không có mức thuế nào được tự gán.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>

<script>
    (() => {
        const scope = document.getElementById('vat-scope');
        const categoryField = document.getElementById('vat-category-field');
        const productsField = document.getElementById('vat-products-field');
        const vatCategory = document.getElementById('vat-category');
        const customRateField = document.getElementById('vat-custom-rate-field');
        const category = document.querySelector('[name="category_id"]');
        const products = document.querySelector('[name="product_ids[]"]');
        const customRate = document.querySelector('[name="vat_rate"]');
        const priceIncludesVat = document.querySelector('[name="price_includes_vat"]');
        const money = value => new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 }).format(value) + ' đ';
        const preview = () => {
            const options = Array.from(products.options);
            const chosen = options.filter(option => scope.value === 'store'
                || (scope.value === 'category' && option.dataset.category === category.value)
                || (scope.value === 'products' && option.selected));
            const rawRate = vatCategory.value === 'custom' ? customRate.value.trim() : vatCategory.selectedOptions[0]?.dataset.rate;
            const rate = rawRate === '' || rawRate === undefined ? NaN : Number(rawRate);
            const validRate = Number.isFinite(rate) && rate >= 0 && rate <= 100;
            const prices = chosen.map(option => {
                const regular = Number(option.dataset.price || 0);
                const sale = Number(option.dataset.salePrice || 0);
                return sale > 0 && sale < regular ? sale : regular;
            });
            const total = prices.reduce((sum, price) => sum + price, 0);
            const vat = validRate ? prices.reduce((sum, price) => sum + Math.round(priceIncludesVat.value === '1'
                ? price * rate / (100 + rate) : price * rate / 100), 0) : null;
            document.getElementById('vat-preview-count').textContent = String(chosen.length);
            document.getElementById('vat-preview-base').textContent = money(total);
            document.getElementById('vat-preview-tax').textContent = vat === null ? 'Chọn thuế suất hợp lệ' : money(vat);
        };
        const sync = () => {
            categoryField.classList.toggle('hidden', scope.value !== 'category');
            productsField.classList.toggle('hidden', scope.value !== 'products');
            customRateField.classList.toggle('hidden', vatCategory.value !== 'custom');
            preview();
        };
        scope.addEventListener('change', sync);
        vatCategory.addEventListener('change', sync);
        category.addEventListener('change', preview);
        products.addEventListener('change', preview);
        customRate.addEventListener('input', preview);
        priceIncludesVat.addEventListener('change', preview);
        sync();
    })();
</script>
@endsection
