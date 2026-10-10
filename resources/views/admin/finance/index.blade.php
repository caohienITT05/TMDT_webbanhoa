@extends('layouts.admin')
@section('title', 'Tài chính & Lợi nhuận')
@section('content')
@include('admin.finance.nav')
@php
    $money = fn ($value) => $value === null ? 'Thiếu dữ liệu' : number_format($value / 100, 0, ',', '.').' đ';
    $displayDate = fn ($value) => \Illuminate\Support\Carbon::parse($value)->format('d/m/Y');
    $labels = ['net_revenue'=>'Doanh thu thuần đơn hoàn thành', 'cogs'=>'Giá vốn hàng bán', 'gross_profit'=>'Lợi nhuận gộp', 'shipping_income'=>'Phí giao hàng thu khách', 'shipping_cost_actual'=>'Chi phí vận chuyển thực tế', 'packaging_cost_actual'=>'Chi phí đóng gói', 'payment_gateway_fee_actual'=>'Phí thanh toán', 'other_direct_cost'=>'Chi phí trực tiếp khác', 'operating_expenses'=>'Chi phí vận hành', 'cancelled_costs'=>'Chi phí đã ghi nhận của đơn hủy', 'profit_before_tax'=>'Lợi nhuận trước thuế', 'estimated_tax'=>'Thuế TNDN dự tính', 'actual_tax'=>'Thuế TNDN thực tế đã xác nhận', 'estimated_indirect_tax'=>'VAT đầu ra tham khảo', 'actual_indirect_tax'=>'VAT thực tế đã xác nhận', 'net_profit_after_tax'=>'Lợi nhuận sau thuế TNDN thực tế', 'estimated_net_profit_after_tax'=>'Lợi nhuận sau thuế TNDN dự tính'];
@endphp
<h1 class="admin-page-title mb-4">Tài chính & Lợi nhuận</h1>
<form class="mb-6 flex flex-wrap items-end gap-3 rounded-xl bg-white p-4 border" method="GET">
    <label>Kỳ<select name="period" class="block rounded-lg border-gray-300">
        @foreach(['today'=>'Hôm nay','week'=>'7 ngày gần nhất','month'=>'Tháng này','quarter'=>'Quý này','year'=>'Năm nay','custom'=>'Tùy chọn'] as $key=>$label)<option value="{{ $key }}" @selected($period === $key)>{{ $label }}</option>@endforeach
    </select></label>
    <label>Từ ngày<input class="block rounded-lg border-gray-300" type="date" name="from_date" value="{{ $from }}"></label>
    <label>Đến ngày<input class="block rounded-lg border-gray-300" type="date" name="to_date" value="{{ $to }}"></label>
    <button class="rounded-lg bg-rose-600 px-5 py-2 text-white">Lọc</button>
    <a class="rounded-lg border border-rose-200 bg-rose-50 px-5 py-2 text-rose-700" href="{{ route('admin.finance.export', ['from_date' => $from, 'to_date' => $to]) }}">Tải toàn bộ dữ liệu kỳ này (CSV)</a>
</form>
<section class="mb-6 rounded-xl border border-rose-100 bg-white p-5 shadow-sm">
    <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-lg font-bold text-gray-900">Dữ liệu báo cáo</h2>
            <p class="mt-1 text-sm text-gray-500">Kỳ {{ $displayDate($from) }} đến {{ $displayDate($to) }} · dữ liệu lấy trực tiếp từ hệ thống.</p>
        </div>
        <span class="rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700">{{ $dataCoverage['orders'] }} đơn trong kỳ</span>
    </div>
    <div class="mb-4 grid gap-3 md:grid-cols-3">
        <div class="rounded-xl bg-rose-600 p-4 text-white"><p class="text-xs text-rose-100">Tổng doanh thu tự động ghi nhận</p><p class="mt-1 text-2xl font-bold">{{ $money($actual['totals']['revenue']) }}</p><p class="mt-1 text-xs text-rose-100">{{ $actual['totals']['recognized_count'] }} đơn đã thanh toán, xác nhận hoặc hoàn thành</p></div>
        <div class="rounded-xl bg-gray-900 p-4 text-white"><p class="text-xs text-gray-300">Tổng giá trị đơn phát sinh</p><p class="mt-1 text-2xl font-bold">{{ $money($actual['totals']['orders_value']) }}</p><p class="mt-1 text-xs text-gray-300">Tự cộng từ {{ $actual['totals']['orders_count'] }} đơn không bị hủy</p></div>
        <div class="rounded-xl bg-amber-50 p-4 text-amber-900"><p class="text-xs text-amber-700">Giá trị chờ ghi nhận</p><p class="mt-1 text-2xl font-bold">{{ $money($actual['totals']['awaiting_revenue']) }}</p><p class="mt-1 text-xs text-amber-700">{{ $actual['totals']['awaiting_count'] }} đơn chưa thanh toán/xác nhận/hoàn thành</p></div>
    </div>
    <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
        <div class="rounded-lg bg-gray-50 p-3"><p class="text-xs text-gray-500">Đơn hoàn thành</p><p class="mt-1 text-lg font-bold">{{ $dataCoverage['completed_orders'] }}</p></div>
        <div class="rounded-lg bg-gray-50 p-3"><p class="text-xs text-gray-500">Đơn dự kiến</p><p class="mt-1 text-lg font-bold">{{ $dataCoverage['forecast_orders'] }}</p></div>
        <div class="rounded-lg bg-gray-50 p-3"><p class="text-xs text-gray-500">Đơn hủy</p><p class="mt-1 text-lg font-bold">{{ $dataCoverage['cancelled_orders'] }}</p></div>
        <div class="rounded-lg bg-gray-50 p-3"><p class="text-xs text-gray-500">Giá vốn dòng hàng</p><p class="mt-1 text-lg font-bold">{{ $dataCoverage['items_with_cost'] }}/{{ $dataCoverage['items'] }}</p></div>
        <div class="rounded-lg bg-gray-50 p-3"><p class="text-xs text-gray-500">Đơn có chi phí thực tế</p><p class="mt-1 text-lg font-bold">{{ $dataCoverage['orders_with_direct_costs'] }}/{{ $dataCoverage['orders'] }}</p></div>
        <div class="rounded-lg bg-gray-50 p-3"><p class="text-xs text-gray-500">Giá vốn sản phẩm</p><p class="mt-1 text-lg font-bold">{{ $dataCoverage['products_with_cost'] }}/{{ $dataCoverage['products'] }}</p></div>
    </div>
    <p class="mt-3 text-xs text-gray-500">Bút toán chi phí vận hành: {{ $dataCoverage['expenses'] }} · Hồ sơ thuế: {{ $dataCoverage['tax_records'] }}. Tỷ lệ thiếu cho biết dữ liệu cần nhập thêm để xác nhận lợi nhuận.</p>
</section>
@if($taxCompliance['profile'])
<section class="mb-6 rounded-xl border border-blue-100 bg-blue-50 p-5 text-blue-950">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div><h2 class="font-bold">Thuế demo 2026 · Doanh nghiệp B2C</h2><p class="mt-1 text-sm">Doanh thu năm ghi nhận: {{ number_format($taxCompliance['cit']['current_revenue'], 0, ',', '.') }} đ · trạng thái: {{ $taxCompliance['cit']['status_label'] }}.</p></div>
        <a class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-blue-800 shadow-sm" href="{{ route('admin.finance.taxes') }}">Xem căn cứ và hồ sơ thuế</a>
    </div>
    <p class="mt-3 text-xs">VAT đầu ra được báo cáo riêng và không bị trừ trực tiếp khỏi lợi nhuận. Hóa đơn điện tử B2C đang ở trạng thái demo chưa kết nối.</p>
</section>
@endif
<p class="mb-4 text-sm text-gray-600">Doanh thu được đọc tự động từ tổng tiền đơn hàng; không phải nhập lại. Đơn đã thanh toán, xác nhận hoặc hoàn thành được ghi nhận vào doanh thu. Đơn còn lại xuất hiện ở giá trị chờ ghi nhận; đơn hủy bị loại. Phần lợi nhuận thực hiện chỉ dùng đơn hoàn thành và chi phí thực tế. Chi phí vận hành theo ngày chi. Hồ sơ thuế thực tế phải khớp chính xác kỳ lọc và đầy đủ các loại thuế đã cấu hình.</p>
<h2 class="text-xl font-bold mb-3">Thực hiện · {{ $actual['totals']['order_count'] }} đơn hoàn thành</h2>
@if($actual['totals']['missing_orders'])<p class="mb-4 rounded-xl bg-amber-50 p-4 text-amber-900">Thiếu dữ liệu chi phí: {{ $actual['totals']['missing_orders'] }} đơn. Chưa đủ dữ liệu giá vốn hoặc chi phí trực tiếp; không thể xác nhận lợi nhuận.</p>@endif
<div class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-4 gap-4 mb-6">
    @foreach($labels as $key=>$label)<div class="rounded-xl bg-white border border-gray-200 shadow-sm p-4"><p class="text-sm text-gray-500">{{ $label }}</p><p class="mt-2 text-xl font-bold">{{ $money($actual['totals'][$key]) }}</p></div>@endforeach
    <div class="rounded-xl bg-white border p-4"><p>Biên lợi nhuận sau thuế thực tế</p><p class="mt-2 text-xl font-bold">{{ $actual['totals']['profit_margin'] === null ? 'Thiếu dữ liệu' : $actual['totals']['profit_margin'].'%' }}</p></div>
</div>
@if($actual['totals']['unallocated_income'] !== 0)
<p class="mb-6 rounded-xl bg-amber-50 p-4">Chênh lệch cần đối chiếu với tổng đơn: {{ $money($actual['totals']['unallocated_income']) }}. Quy trình thanh toán cũ gộp tiền thiệp/gói quà vào tổng đơn nhưng không lưu riêng. Báo cáo tài chính dùng công thức doanh thu sản phẩm − giảm giá + phí giao hàng; chênh lệch này chưa được đưa vào lợi nhuận và cần đối chiếu trước khi dùng làm báo cáo kế toán.</p>
@endif
@php
    $comparisonState = fn ($state) => match ($state) {
        'up' => ['↑', 'text-emerald-700 bg-emerald-50'],
        'down' => ['↓', 'text-red-700 bg-red-50'],
        'new' => ['↑', 'text-blue-700 bg-blue-50'],
        'steady' => ['→', 'text-gray-700 bg-gray-100'],
        default => ['—', 'text-amber-700 bg-amber-50'],
    };
    $comparisonText = function ($metric) use ($comparisonState) {
        [$icon] = $comparisonState($metric['state']);
        if ($metric['state'] === 'unavailable') return 'Chưa đủ dữ liệu tài chính';
        if ($metric['state'] === 'new') return 'Mới phát sinh';
        return $icon . ' ' . number_format((float) $metric['percentage'], 2, ',', '.') . '% so với kỳ trước';
    };
@endphp
<section class="mb-6 rounded-xl border bg-white p-5">
    <div class="mb-5">
        <h2 class="text-xl font-bold text-gray-900">So sánh doanh thu & lợi nhuận</h2>
        <p class="mt-1 text-sm text-gray-600">Doanh thu dùng cùng dữ liệu ghi nhận của báo cáo. Lợi nhuận là lợi nhuận trước thuế; khoảng trống thể hiện dữ liệu chi phí chưa đủ.</p>
    </div>
    <div class="grid gap-5 xl:grid-cols-2">
        @foreach(['week' => 'Theo tuần', 'month' => 'Theo tháng'] as $key => $title)
            @php $periodComparison = $comparison[$key]; @endphp
            <article class="rounded-xl border border-gray-200 p-4">
                <h3 class="font-semibold text-gray-900">{{ $title }}</h3>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach(['revenue' => 'Doanh thu', 'profit' => 'Lợi nhuận trước thuế'] as $metricKey => $metricLabel)
                        @php
                            $metric = $periodComparison[$metricKey];
                            [$icon, $class] = $comparisonState($metric['state']);
                        @endphp
                        <div class="rounded-lg bg-gray-50 p-4">
                            <p class="text-sm font-medium text-gray-700">{{ $metricLabel }}</p>
                            <p class="mt-2 text-xl font-bold text-gray-900">{{ $money($metric['current']) }}</p>
                            <p class="mt-1 text-xs text-gray-500">{{ $periodComparison['current_label'] }} · {{ $money($metric['previous']) }} {{ $periodComparison['previous_label'] }}</p>
                            <p class="mt-3 text-sm font-semibold {{ $class }} inline-flex rounded-full px-2.5 py-1">{{ $comparisonText($metric) }}</p>
                            @if($metric['difference'] !== null)<p class="mt-2 text-xs text-gray-500">Chênh lệch: {{ $money($metric['difference']) }}</p>@endif
                        </div>
                    @endforeach
                </div>
            </article>
        @endforeach
    </div>
</section>
<section class="mb-6 grid gap-6 xl:grid-cols-2">
    @foreach(['weekly' => ['Doanh thu & lợi nhuận theo tuần', 'Theo đúng kỳ báo cáo đang chọn; mỗi cột có một mốc tuần riêng.'], 'monthly' => ['Doanh thu & lợi nhuận theo tháng', 'Theo đúng kỳ báo cáo đang chọn; mỗi cột có một mốc tháng riêng.']] as $series => [$title, $subtitle])
        <article class="rounded-xl border bg-white p-5">
            <h2 class="text-lg font-bold">{{ $title }}</h2>
            <p class="mt-1 text-sm text-gray-600">{{ $subtitle }}</p>
            <div class="mt-4 overflow-x-auto"><div class="h-72 min-w-full finance-comparison-stage"><canvas class="finance-comparison-chart" data-series="{{ $series }}" aria-label="{{ $title }}" role="img"></canvas></div></div>
            <p class="mt-3 text-xs text-gray-500" data-finance-comparison-tooltip="{{ $series }}">Di chuột trên biểu đồ để xem chi tiết từng kỳ.</p>
            <div class="mt-4 max-h-56 overflow-y-auto rounded-lg border border-gray-100">
                <table class="w-full text-left text-xs">
                    <thead class="sticky top-0 bg-gray-50 text-gray-600"><tr><th class="p-2">Kỳ</th><th class="p-2">Doanh thu</th><th class="p-2">Lợi nhuận</th><th class="p-2">Biên</th></tr></thead>
                    <tbody>
                        @forelse($comparison[$series . '_trend'] as $row)
                            <tr class="border-t border-gray-100"><td class="p-2 font-medium text-gray-800">{{ $row['label'] }}</td><td class="p-2">{{ $money($row['revenue']) }}</td><td class="p-2">{{ $money($row['profit']) }}</td><td class="p-2">{{ $row['margin'] === null ? 'Thiếu dữ liệu' : number_format($row['margin'], 2, ',', '.') . '%' }}</td></tr>
                        @empty
                            <tr class="border-t border-gray-100"><td colspan="4" class="p-3 text-gray-500">Chưa có đơn hoàn thành để lập xu hướng.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    @endforeach
</section>
<div class="mb-6 rounded-xl bg-white border p-5">
    <h2 class="text-lg font-bold mb-3">Xu hướng theo ngày tạo đơn hoàn thành</h2>
    <p class="text-sm mb-3">Lợi nhuận trên biểu đồ là lợi nhuận trực tiếp trước chi phí vận hành và thuế. Khoảng trống thể hiện thiếu dữ liệu.</p>
    <div class="h-72"><canvas id="finance-chart" aria-label="Doanh thu, giá vốn, chi phí và lợi nhuận theo ngày" role="img"></canvas></div>
    <div class="overflow-x-auto mt-4"><table class="w-full text-sm text-left"><thead><tr><th class="p-2">Ngày</th><th>Doanh thu thuần</th><th>Giá vốn</th><th>Chi phí trực tiếp khác</th><th>Lợi nhuận trực tiếp</th></tr></thead><tbody>
    @forelse($actual['daily'] as $date=>$day)
        @php $direct = in_array(null, array_intersect_key($day, array_flip(\App\Services\FinanceAnalyticsService::COST_FIELDS)), true) ? null : array_sum(array_intersect_key($day, array_flip(\App\Services\FinanceAnalyticsService::COST_FIELDS))); @endphp
        <tr class="border-t"><td class="p-2">{{ $displayDate($date) }}</td><td>{{ $money($day['net_product_revenue'] + $day['shipping_income']) }}</td><td>{{ $money($day['cogs']) }}</td><td>{{ $money($direct) }}</td><td>{{ $money($day['order_profit']) }}</td></tr>
    @empty<tr><td colspan="5" class="p-4">Chưa có đơn hoàn thành trong kỳ.</td></tr>@endforelse
    </tbody></table></div>
</div>
<h2 class="text-xl font-bold mb-3">Dự kiến · {{ $forecast['totals']['order_count'] }} đơn chưa hoàn thành</h2>
<div class="grid md:grid-cols-3 gap-4 mb-6">
    @foreach(['net_revenue'=>'Doanh thu thuần dự kiến', 'cogs'=>'Giá vốn dự kiến', 'order_profit'=>'Lợi nhuận trực tiếp dự kiến'] as $key=>$label)<div class="rounded-xl bg-white border p-4"><p>{{ $label }}</p><strong>{{ $money($forecast['totals'][$key]) }}</strong></div>@endforeach
</div>
<div class="rounded-xl bg-white border p-5 overflow-x-auto"><h2 class="text-lg font-bold mb-3">Đối chiếu các đơn hoàn thành</h2><table class="w-full text-sm text-left"><thead><tr><th>Đơn hàng</th><th>Doanh thu thuần</th><th>Giá vốn</th><th>Chi phí trực tiếp khác</th><th>Lợi nhuận trực tiếp</th><th>Dữ liệu</th></tr></thead><tbody>
@forelse($actual['rows'] as $row)
@php $directOther = in_array(null, array_intersect_key($row['summary'], array_flip(\App\Services\FinanceAnalyticsService::COST_FIELDS)), true) ? null : array_sum(array_intersect_key($row['summary'], array_flip(\App\Services\FinanceAnalyticsService::COST_FIELDS))); @endphp
<tr class="border-t"><td class="py-3"><a class="text-rose-600" href="{{ route('admin.finance.order', $row['order']) }}">{{ $row['order']->order_code }}</a></td><td>{{ $money($row['summary']['net_product_revenue'] + $row['summary']['shipping_income']) }}</td><td>{{ $money($row['summary']['cogs']) }}</td><td>{{ $money($directOther) }}</td><td>{{ $money($row['summary']['order_profit']) }}</td><td>{{ $row['summary']['data_status'] }}</td></tr>
@empty<tr><td class="py-4" colspan="6">Chưa có dữ liệu.</td></tr>@endforelse
</tbody></table></div>
<p class="mt-6 text-sm text-gray-500">Các chỉ số thuế được tính từ cấu hình và dữ liệu do người quản trị cung cấp. Nghĩa vụ thuế chính thức cần được đối chiếu với quy định pháp luật hiện hành và hồ sơ kế toán thực tế.</p>
<script type="application/json" id="finance-series">{!! json_encode($actual['daily'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
<script type="application/json" id="finance-comparison-series">{!! json_encode(['weekly' => $comparison['weekly_trend'], 'monthly' => $comparison['monthly_trend']], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
<script src="{{ asset('js/finance-chart.js') }}" defer></script>
<script src="{{ asset('js/finance-comparison-chart.js') }}" defer></script>
@endsection
