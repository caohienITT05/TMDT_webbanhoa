@extends('layouts.admin')
@section('title', 'Cấu hình & Hồ sơ thuế')
@section('content')
@include('admin.finance.nav')
@php
    $displayDate = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format('d/m/Y') : '—';
    $money = fn ($value) => $value === null ? 'Thiếu dữ liệu' : number_format($value, 0, ',', '.').' đ';
    $moneyCents = fn ($value) => $value === null ? 'Thiếu dữ liệu' : number_format($value / 100, 0, ',', '.').' đ';
    $taxBases = ['net_revenue'=>'Doanh thu thuần gồm phí giao hàng','net_product_revenue'=>'Doanh thu sản phẩm sau giảm giá','profit_before_tax'=>'Lợi nhuận trước thuế'];
    $taxStatuses = ['estimated'=>'Dự tính','confirmed'=>'Đã xác nhận','paid'=>'Đã nộp','estimated_exempt'=>'Dự kiến được miễn','confirmed_exempt'=>'Đã xác nhận miễn'];
    $taxCategories = ['income'=>'Thuế thu nhập','indirect'=>'Thuế gián thu / VAT'];
    $calculationMethods = ['exclusive'=>'Cơ sở chưa gồm thuế','inclusive'=>'Cơ sở đã gồm thuế','corporate_income_2026'=>'Tự động đánh giá thuế TNDN 2026','catalog_vat_2026'=>'Theo phân loại VAT sản phẩm'];
    $vatCategories = collect(\App\Services\TaxComplianceService::VAT_CATEGORIES)->mapWithKeys(fn ($row, $key) => [$key => $row['label']])->all();
    $profile = $compliance['profile'];
    $cit = $compliance['cit'];
    $financial = $compliance['financial'];
@endphp

<h1 class="admin-page-title mb-4">Cấu hình & Hồ sơ thuế</h1>
<p class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">Các số liệu thuế trong BloomGift phục vụ mục đích mô phỏng, quản trị và dự tính cho hệ thống demo B2C. Nghĩa vụ thuế thực tế cần được đối chiếu với hồ sơ kế toán, tình trạng pháp lý của doanh nghiệp, nhóm hàng hóa, điều kiện áp dụng ưu đãi hoặc miễn thuế và quy định pháp luật có hiệu lực tại kỳ tính thuế.</p>

<section class="mb-6 rounded-xl border border-rose-100 bg-white p-5 shadow-sm">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div><h2 class="text-lg font-bold">Hồ sơ doanh nghiệp demo</h2><p class="mt-1 text-sm text-gray-600">Doanh nghiệp thành lập theo pháp luật Việt Nam · bán lẻ trực tuyến hoa và quà · mô hình B2C.</p></div>
        <span class="rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700">Năm tính thuế 2026</span>
    </div>
    @if($profile)
    <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-lg bg-gray-50 p-4"><p class="text-xs text-gray-500">Loại hình doanh nghiệp</p><p class="mt-1 font-bold">Doanh nghiệp Việt Nam</p></div>
        <div class="rounded-lg bg-gray-50 p-4"><p class="text-xs text-gray-500">Mô hình kinh doanh</p><p class="mt-1 font-bold">B2C · bán trực tiếp cho người tiêu dùng</p></div>
        <div class="rounded-lg bg-gray-50 p-4"><p class="text-xs text-gray-500">Tình trạng kỳ tính thuế</p><p class="mt-1 font-bold">Doanh nghiệp mới trong năm 2026</p></div>
        <div class="rounded-lg bg-gray-50 p-4"><p class="text-xs text-gray-500">Nguồn số liệu</p><p class="mt-1 font-bold">Dữ liệu quản trị trong hệ thống</p></div>
    </div>
    <div class="mt-4 rounded-lg bg-blue-50 p-4 text-sm text-blue-950">
        <strong>Giả định hồ sơ demo:</strong> doanh nghiệp Việt Nam và B2C. Điều kiện chia/tách để xét giảm 30% chưa được mặc định; quản trị viên cần xác nhận ở biểu mẫu bên dưới. Các ưu đãi khác vẫn cần đối chiếu hồ sơ pháp lý.
    </div>
    <form method="POST" action="{{ route('admin.finance.tax-eligibility.save') }}" class="mt-4 grid gap-3 rounded-lg border p-4 md:grid-cols-[1fr_auto]">@csrf
        <label class="text-sm font-medium">Tình trạng doanh nghiệp có được hình thành do chia/tách thuộc trường hợp bị loại trừ?
            <select name="split_status" class="mt-1 block w-full rounded-lg border-gray-300"><option value="unknown" @selected(($profile->is_split_from_large_enterprise ?? null) === null)>Chưa xác minh</option><option value="not_split" @selected(($profile->is_split_from_large_enterprise ?? null) === false || ($profile->is_split_from_large_enterprise ?? null) === 0)>Không thuộc trường hợp này</option><option value="split" @selected(($profile->is_split_from_large_enterprise ?? null) === true || ($profile->is_split_from_large_enterprise ?? null) === 1)>Có thuộc trường hợp này</option></select>
        </label><button class="self-end rounded-lg bg-rose-600 px-4 py-2 text-white">Lưu tình trạng</button>
        <p class="text-xs text-gray-600 md:col-span-2">Chỉ đánh giá giảm 30% khi doanh thu năm không quá 10 tỷ đồng và tình trạng chia/tách đã được xác nhận. Chưa xác minh thì không tự áp dụng ưu đãi.</p>
    </form>
    @else
    <p class="mt-4 rounded-lg bg-amber-50 p-4 text-amber-900">Chưa cấu hình hồ sơ pháp lý cho cửa hàng.</p>
    @endif
</section>

<section class="mb-6 rounded-xl border bg-white p-5 shadow-sm">
    <div class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="text-lg font-bold">Thuế thu nhập doanh nghiệp</h2><p class="mt-1 text-sm text-gray-600">Đánh giá tự động theo hồ sơ doanh nghiệp và doanh thu năm, không dùng quy tắc của hộ kinh doanh.</p></div><span class="rounded-full {{ str_contains($cit['status'], 'exempt') ? 'bg-green-50 text-green-800' : 'bg-amber-50 text-amber-800' }} px-3 py-1 text-xs font-semibold">{{ $cit['status_label'] }}</span></div>
    <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-lg bg-gray-50 p-4"><p class="text-xs text-gray-500">Doanh thu đã ghi nhận năm 2026</p><p class="mt-1 text-lg font-bold">{{ $money($cit['current_revenue']) }}</p></div>
        <div class="rounded-lg bg-gray-50 p-4"><p class="text-xs text-gray-500">Doanh thu dự kiến năm</p><p class="mt-1 text-lg font-bold">{{ $money($cit['projected_revenue']) }}</p><p class="mt-1 text-xs text-gray-500">Chưa nhập kế hoạch riêng nên đang dùng dữ liệu đã ghi nhận</p></div>
        <div class="rounded-lg bg-gray-50 p-4"><p class="text-xs text-gray-500">Doanh thu quyết toán năm</p><p class="mt-1 text-lg font-bold">{{ $cit['actual_revenue'] === null ? 'Chưa quyết toán' : $money($cit['actual_revenue']) }}</p></div>
        <div class="rounded-lg bg-gray-50 p-4"><p class="text-xs text-gray-500">Doanh thu năm trước liền kề</p><p class="mt-1 text-lg font-bold">{{ $cit['prior_year_revenue'] === null ? 'Không áp dụng cho hồ sơ mới' : $money($cit['prior_year_revenue']) }}</p></div>
        <div class="rounded-lg bg-blue-50 p-4 text-blue-950"><p class="text-xs text-blue-700">Giảm 30% CIT theo Nghị quyết 43</p><p class="mt-1 text-lg font-bold">{{ $cit['cit_reduction_percent'] ? 'Đủ điều kiện dự kiến' : (['needs_confirmation'=>'Chờ xác minh chia/tách','excluded_split'=>'Thuộc trường hợp loại trừ','over_revenue_limit'=>'Doanh thu vượt 10 tỷ','not_applicable'=>'Không thuộc kỳ áp dụng'][$cit['cit_reduction_status']] ?? 'Chưa đủ dữ liệu') }}</p><p class="mt-1 text-xs text-blue-800">Tính trên thuế sau ưu đãi khác, cho kỳ 2026–2027 nếu doanh thu năm không quá 10 tỷ đồng.</p></div>
        <div class="rounded-lg bg-green-50 p-4 text-green-900"><p class="text-xs text-green-700">Trạng thái thuế</p><p class="mt-1 text-lg font-bold">{{ $cit['status_label'] }}</p><p class="mt-1 text-xs text-green-700">{{ $cit['reason'] }}</p></div>
        <div class="rounded-lg bg-gray-50 p-4"><p class="text-xs text-gray-500">Mức áp dụng nếu thuộc diện chịu thuế</p><p class="mt-1 text-lg font-bold">{{ $cit['applicable_rate'] === null ? 'Chưa xác định' : number_format($cit['applicable_rate'], 0, ',', '.') . '%' }}</p><p class="mt-1 text-xs text-gray-500">Miễn thuế là trạng thái riêng, không phải thuế suất 0%</p></div>
        <div class="rounded-lg bg-gray-50 p-4"><p class="text-xs text-gray-500">Thu nhập tính thuế dự kiến</p><p class="mt-1 text-lg font-bold">{{ $moneyCents($financial['profit_before_tax']) }}</p><p class="mt-1 text-xs text-gray-500">Số quản trị trước điều chỉnh quyết toán</p></div>
        <div class="rounded-lg bg-rose-50 p-4 text-rose-900"><p class="text-xs text-rose-700">Thuế TNDN dự tính</p><p class="mt-1 text-lg font-bold">{{ $moneyCents($financial['estimated_tax']) }}</p><p class="mt-1 text-xs text-rose-700">Thuế thực tế: {{ $moneyCents($financial['actual_tax']) }}</p></div>
    </div>
    <div class="mt-4 overflow-x-auto"><table class="w-full min-w-[680px] text-left text-sm"><thead><tr><th class="py-2">Doanh thu dùng đánh giá</th><th>Điều kiện hồ sơ</th><th>Kết quả demo</th></tr></thead><tbody>
        <tr class="border-t"><td class="py-2">Không quá 1 tỷ đồng</td><td>Đủ điều kiện, không thuộc trường hợp loại trừ</td><td>Dự kiến được miễn</td></tr>
        <tr class="border-t"><td class="py-2">Trên 1 tỷ đến 3 tỷ đồng</td><td>Đủ điều kiện áp dụng mức doanh nghiệp nhỏ</td><td>15%</td></tr>
        <tr class="border-t"><td class="py-2">Trên 3 tỷ đến 50 tỷ đồng</td><td>Đủ điều kiện áp dụng mức doanh nghiệp nhỏ và vừa</td><td>17%</td></tr>
        <tr class="border-t"><td class="py-2">Trên 50 tỷ đồng hoặc có điều kiện loại trừ</td><td>Không thuộc mức miễn, 15% hoặc 17%</td><td>20%</td></tr>
    </tbody></table></div>
</section>

<section class="mb-6 rounded-xl border bg-white p-5 shadow-sm">
    <div class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="text-lg font-bold">Thuế giá trị gia tăng (VAT)</h2><p class="mt-1 text-sm text-gray-600">VAT được báo cáo riêng và không bị trừ trực tiếp khỏi lợi nhuận sau thuế TNDN.</p></div><span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-800">VAT đầu ra tham khảo</span></div>
    <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-lg bg-gray-50 p-4"><p class="text-xs text-gray-500">Phương pháp giá</p><p class="mt-1 font-bold">{{ $profile?->prices_include_vat ? 'Giá đã bao gồm VAT' : 'Giá chưa bao gồm VAT' }}</p><p class="mt-1 text-xs text-gray-500">{{ $profile?->prices_include_vat ? 'Công thức mặc định: doanh thu × thuế suất / (100 + thuế suất)' : 'Công thức mặc định: doanh thu × thuế suất' }}</p></div>
        <div class="rounded-lg bg-gray-50 p-4"><p class="text-xs text-gray-500">Thuế suất mặc định demo</p><p class="mt-1 text-lg font-bold">{{ $profile ? number_format((float) $profile->default_vat_rate, 0, ',', '.') . '%' : 'Chưa cấu hình' }}</p><p class="mt-1 text-xs text-gray-500">Không tự dùng để tính VAT của sản phẩm chưa phân loại</p></div>
        <div class="rounded-lg bg-gray-50 p-4"><p class="text-xs text-gray-500">Doanh thu tính VAT trong kỳ</p><p class="mt-1 text-lg font-bold">{{ $moneyCents($financial['estimated_indirect_tax_base']) }}</p><p class="mt-1 text-xs text-gray-500">Từ {{ $displayDate($compliance['period_from']) }} đến {{ $displayDate($compliance['period_to']) }}</p></div>
        <div class="rounded-lg bg-blue-50 p-4 text-blue-900"><p class="text-xs text-blue-700">VAT đầu ra dự tính</p><p class="mt-1 text-lg font-bold">{{ $moneyCents($financial['estimated_indirect_tax']) }}</p><p class="mt-1 text-xs text-blue-700">Chưa trừ VAT đầu vào</p></div>
        <div class="rounded-lg bg-amber-50 p-4 text-amber-900"><p class="text-xs text-amber-700">Sản phẩm chưa phân loại thuế</p><p class="mt-1 text-lg font-bold">{{ $compliance['unclassified_products'] }}/{{ $compliance['unclassified_products'] + $compliance['mapped_products'] }}</p><p class="mt-1 text-xs text-amber-700">VAT của phần chưa phân loại được hiển thị là thiếu dữ liệu, không tự gán mức thuế.</p></div>
        <div class="rounded-lg bg-amber-50 p-4 text-amber-900"><p class="text-xs text-amber-700">Doanh thu chưa đủ phân loại VAT</p><p class="mt-1 text-lg font-bold">{{ $moneyCents($financial['unclassified_indirect_tax_base']) }}</p><p class="mt-1 text-xs text-amber-700">Gồm sản phẩm chưa mapping và khoản giao hàng cần xác định cách xử lý.</p></div>
        <div class="rounded-lg bg-gray-50 p-4"><p class="text-xs text-gray-500">Sản phẩm đã phân loại</p><p class="mt-1 text-lg font-bold">{{ $compliance['mapped_products'] }}</p><p class="mt-1 text-xs text-gray-500">Hỗ trợ không chịu thuế, 0%, 5%, 8%, 10% và tùy chỉnh</p></div>
        <div class="rounded-lg bg-gray-50 p-4"><p class="text-xs text-gray-500">Ngày hiệu lực chính sách giảm VAT</p><p class="mt-1 font-bold">01/07/2025 – 31/12/2026</p></div>
        <div class="rounded-lg bg-gray-50 p-4"><p class="text-xs text-gray-500">VAT thực tế đã xác nhận</p><p class="mt-1 text-lg font-bold">{{ $moneyCents($financial['actual_indirect_tax']) }}</p></div>
    </div>
    <p class="mt-4 rounded-lg bg-blue-50 p-4 text-sm text-blue-950">Mức 8% chỉ là cấu hình mặc định cho hàng hóa hoặc dịch vụ đủ điều kiện giảm từ 10%. Hoa tươi chưa chế biến có thể thuộc mức 5%; quà tặng và sản phẩm kết hợp phải được phân loại theo hóa đơn thực tế.</p>
</section>

<section class="mb-6 rounded-xl border bg-white p-5 shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-4"><div><h2 class="text-lg font-bold">Cấu hình VAT theo nhiều phạm vi</h2><p class="mt-1 text-sm text-gray-600">Áp dụng cho toàn cửa hàng, danh mục hoặc nhiều sản phẩm; lịch sử quy tắc được giữ riêng.</p></div><a href="{{ route('admin.finance.vat-products') }}" class="rounded-lg bg-rose-600 px-5 py-2 font-semibold text-white">Mở cấu hình VAT sản phẩm</a></div>
    <p class="mt-3 text-sm text-gray-600">Mapping một sản phẩm cũ được giữ để tương thích dữ liệu hiện có. Không thể ghi đè mapping cũ từ đây; quy tắc mới phải được thêm tại trang cấu hình để không làm thay đổi lịch sử đơn.</p>
    @if($productTaxProfiles->isNotEmpty())
    <div class="mt-5 overflow-x-auto"><table class="w-full min-w-[720px] text-left text-sm"><thead><tr><th>Sản phẩm</th><th>Phân loại</th><th>Thuế suất</th><th>Giá</th><th>Hiệu lực</th></tr></thead><tbody>@foreach($productTaxProfiles as $mapping)<tr class="border-t"><td class="py-3">{{ $mapping->product_name }}</td><td>{{ $vatCategories[$mapping->vat_category] ?? $mapping->vat_category }}</td><td>{{ number_format((float) $mapping->vat_rate, 2, ',', '.') }}%</td><td>{{ $mapping->price_includes_vat ? 'Đã gồm VAT' : 'Chưa gồm VAT' }}</td><td>{{ $displayDate($mapping->effective_from) }} – {{ $displayDate($mapping->effective_to) }}</td></tr>@endforeach</tbody></table></div>
    @endif
</section>

<section class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-5 text-amber-950">
    <h2 class="text-lg font-bold">Hóa đơn điện tử</h2>
    <div class="mt-3 grid gap-3 md:grid-cols-3"><div><p class="text-xs text-amber-700">Trạng thái tích hợp</p><p class="font-bold">Chưa kết nối · đang mô phỏng</p></div><div><p class="text-xs text-amber-700">Đơn đã ghi nhận cần theo dõi hóa đơn</p><p class="font-bold">{{ $compliance['einvoice_order_count'] }} đơn</p></div><div><p class="text-xs text-amber-700">Phạm vi</p><p class="font-bold">Bán lẻ B2C trực tiếp cho người tiêu dùng</p></div></div>
    <p class="mt-3 text-sm">Hệ thống không tạo API giả và chưa gửi dữ liệu tới cơ quan thuế. Cần tích hợp giải pháp hóa đơn điện tử hợp lệ trước khi vận hành thật.</p>
</section>

<section class="mb-6 rounded-xl border bg-white p-5">
    <h2 class="text-lg font-bold">Căn cứ pháp lý đang hiển thị cho bản demo</h2>
    <ul class="mt-3 list-disc space-y-2 pl-5 text-sm">
        <li>Luật Thuế thu nhập doanh nghiệp số 67/2025/QH15.</li>
        <li>Nghị định số 320/2025/NĐ-CP.</li>
        <li><a class="text-rose-700 underline" href="https://vanban.chinhphu.vn/?classid=1&docid=219330&orggroupid=1&pageid=27160" target="_blank" rel="noopener">Nghị quyết số 43/2026/QH16</a>, giảm 30% số thuế TNDN phải nộp cho kỳ 2026–2027 nếu doanh thu năm không quá 10 tỷ đồng và không thuộc ngoại lệ chia/tách.</li>
        <li><a class="text-rose-700 underline" href="https://vanban.chinhphu.vn/?classid=1&docid=217960&pageid=27160&typegroupid=4" target="_blank" rel="noopener">Nghị định số 141/2026/NĐ-CP</a>, áp dụng từ 01/01/2026.</li>
        <li>Luật Thuế giá trị gia tăng số 48/2024/QH15.</li>
        <li><a class="text-rose-700 underline" href="https://vanban.chinhphu.vn/default.aspx?docid=214209&pageid=27160" target="_blank" rel="noopener">Nghị quyết số 204/2025/QH15</a>, giảm VAT với nhóm đủ điều kiện đến hết 31/12/2026.</li>
        <li><a class="text-rose-700 underline" href="https://vanban.chinhphu.vn/?classid=1&docid=213179&pageid=27160" target="_blank" rel="noopener">Nghị định số 70/2025/NĐ-CP</a> về hóa đơn điện tử.</li>
    </ul>
</section>

<details class="mb-6 rounded-xl border bg-white p-5"><summary class="cursor-pointer text-lg font-bold">Quy tắc thuế tùy chỉnh</summary>
<form action="{{ route('admin.finance.tax-settings.save') }}" method="POST" class="mt-4 grid gap-4 md:grid-cols-2">@csrf
<label>Mã loại thuế (nội bộ)<input required name="tax_type" placeholder="Ví dụ: thue_bo_sung" value="{{ old('tax_type') }}" class="block w-full rounded-lg border-gray-300"></label>
<label>Tên thuế<input required name="tax_name" value="{{ old('tax_name') }}" class="block w-full rounded-lg border-gray-300"></label>
<label>Nhóm thuế<select name="tax_category" class="block w-full rounded-lg border-gray-300"><option value="income">Thuế thu nhập</option><option value="indirect">Thuế gián thu / VAT</option></select></label>
<label>Cơ sở tính<select name="tax_base" class="block w-full rounded-lg border-gray-300"><option value="net_revenue">Doanh thu thuần gồm phí giao hàng</option><option value="net_product_revenue">Doanh thu sản phẩm sau giảm giá</option><option value="profit_before_tax">Lợi nhuận trước thuế</option></select></label>
<label>Cách tính<select name="calculation_method" class="block w-full rounded-lg border-gray-300"><option value="exclusive">Cơ sở chưa gồm thuế</option><option value="inclusive">Cơ sở đã gồm thuế</option></select></label>
<label>Thuế suất (%)<input required type="number" min="0" max="100" step="0.0001" name="rate" value="{{ old('rate') }}" class="block w-full rounded-lg border-gray-300"></label>
<label>Hiệu lực từ<input required type="date" name="effective_from" value="{{ old('effective_from') }}" class="block w-full rounded-lg border-gray-300"></label>
<label>Hiệu lực đến<input type="date" name="effective_to" value="{{ old('effective_to') }}" class="block w-full rounded-lg border-gray-300"></label>
<label>Mô tả<textarea name="description" class="block w-full rounded-lg border-gray-300">{{ old('description') }}</textarea></label>
<label>Căn cứ pháp lý<input name="legal_reference" maxlength="500" value="{{ old('legal_reference') }}" class="block w-full rounded-lg border-gray-300"></label>
<label>Liên kết văn bản chính thức<input type="url" name="legal_reference_url" maxlength="1000" value="{{ old('legal_reference_url') }}" class="block w-full rounded-lg border-gray-300"></label>
<label>Ghi chú<textarea name="note" class="block w-full rounded-lg border-gray-300">{{ old('note') }}</textarea></label>
<div><button class="rounded-lg bg-rose-600 px-5 py-2 text-white">Thêm cấu hình</button></div></form>
<div class="mt-5 overflow-x-auto"><table class="w-full min-w-[880px] text-left text-sm"><thead><tr><th>Loại / tên</th><th>Nhóm</th><th>Cơ sở / cách tính</th><th>Mức</th><th>Từ ngày</th><th>Đến ngày</th></tr></thead><tbody>
@foreach($settings as $setting)<tr class="border-t"><td class="py-3"><div>{{ $setting->tax_type }} / {{ $setting->tax_name }}</div>@if($setting->legal_reference_url)<a class="text-xs text-rose-700 underline" href="{{ $setting->legal_reference_url }}" target="_blank" rel="noopener">Xem văn bản chính thức</a>@endif</td><td>{{ $taxCategories[$setting->tax_category] ?? $setting->tax_category }}</td><td>{{ $taxBases[$setting->tax_base] ?? $setting->tax_base }}<div class="text-xs text-gray-500">{{ $calculationMethods[$setting->calculation_method] ?? $setting->calculation_method }}</div></td><td>{{ $setting->calculation_method === 'corporate_income_2026' ? number_format($cit['applicable_rate'], 0, ',', '.').'% nếu chịu thuế' : number_format((float) $setting->rate, 2, ',', '.').'%' }}</td><td>{{ $displayDate($setting->effective_from) }}</td><td><div class="mb-1 text-xs text-gray-500">{{ $displayDate($setting->effective_to) }}</div><form class="flex gap-2" method="POST" action="{{ route('admin.finance.tax-settings.close', $setting->id) }}">@csrf @method('PATCH')<input aria-label="Kết thúc {{ $setting->tax_name }}" required type="date" name="effective_to" value="{{ $setting->effective_to }}" class="rounded-lg border-gray-300"><button class="text-rose-600">Lưu ngày kết thúc</button></form></td></tr>@endforeach
</tbody></table></div></details>

<details class="mb-6 rounded-xl border bg-white p-5"><summary class="cursor-pointer text-lg font-bold">Nhập và đối chiếu hồ sơ thuế theo kỳ</summary>
<form method="POST" action="{{ route('admin.finance.tax-records.save') }}" class="mt-4 grid gap-4 md:grid-cols-2">@csrf
<p class="md:col-span-2 text-sm">Nhập cùng mã loại thuế và đúng ngày bắt đầu, kết thúc để cập nhật hồ sơ đã có. Thuế thực tế chỉ dùng khi trạng thái đã xác nhận hoặc đã nộp. Trạng thái miễn được lưu riêng với số phải nộp bằng 0.</p>
<label>Từ ngày<input required type="date" name="period_start" value="{{ old('period_start') }}" class="block w-full rounded-lg border-gray-300"></label>
<label>Đến ngày<input required type="date" name="period_end" value="{{ old('period_end') }}" class="block w-full rounded-lg border-gray-300"></label>
<label>Mã loại thuế<input required name="tax_type" value="{{ old('tax_type') }}" class="block w-full rounded-lg border-gray-300"></label>
<label>Nhóm thuế<select name="tax_category" class="block w-full rounded-lg border-gray-300"><option value="income">Thuế thu nhập</option><option value="indirect">Thuế gián thu / VAT</option></select></label>
<label>Số tiền tính thuế (VNĐ)<input required type="number" min="0" step="0.01" name="taxable_amount" value="{{ old('taxable_amount') }}" class="block w-full rounded-lg border-gray-300"></label>
<label>Thuế suất (%)<input required type="number" min="0" max="100" step="0.0001" name="tax_rate" value="{{ old('tax_rate') }}" class="block w-full rounded-lg border-gray-300"></label>
<label>Cách tính<select name="calculation_method" class="block w-full rounded-lg border-gray-300"><option value="exclusive">Cơ sở chưa gồm thuế</option><option value="inclusive">Cơ sở đã gồm thuế</option></select></label>
<label>Thuế thực tế (VNĐ)<input type="number" min="0" step="0.01" name="actual_tax" value="{{ old('actual_tax') }}" class="block w-full rounded-lg border-gray-300"></label>
<label>Trạng thái<select name="status" class="block w-full rounded-lg border-gray-300">@foreach($taxStatuses as $key=>$label)<option value="{{ $key }}" @selected(old('status') === $key)>{{ $label }}</option>@endforeach</select></label>
<label>Ghi chú<textarea name="note" class="block w-full rounded-lg border-gray-300">{{ old('note') }}</textarea></label>
<div><button class="rounded-lg bg-rose-600 px-5 py-2 text-white">Lưu hồ sơ thuế</button></div></form>
<div class="mt-5 overflow-x-auto"><table class="w-full min-w-[720px] text-left text-sm"><thead><tr><th>Kỳ</th><th>Loại</th><th>Nhóm</th><th>Dự tính</th><th>Thực tế</th><th>Trạng thái</th></tr></thead><tbody>
@forelse($records as $record)<tr class="border-t"><td class="py-3">{{ $displayDate($record->period_start) }} → {{ $displayDate($record->period_end) }}</td><td>{{ $record->tax_type }}</td><td>{{ $taxCategories[$record->tax_category] ?? $record->tax_category }}</td><td>{{ number_format($record->estimated_tax, 0, ',', '.') }} đ</td><td>{{ $record->actual_tax === null ? 'Chưa xác nhận' : number_format($record->actual_tax, 0, ',', '.').' đ' }}</td><td>{{ $taxStatuses[$record->status] ?? $record->status }}</td></tr>@empty<tr><td colspan="6" class="py-4">Chưa có hồ sơ thuế thực tế.</td></tr>@endforelse
</tbody></table>{{ $records->links() }}</div></details>
@endsection
