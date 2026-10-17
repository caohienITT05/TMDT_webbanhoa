@extends('layouts.admin')
@section('title', 'Doanh thu & Lợi nhuận')
@section('content')
@include('admin.finance.nav')
@php
    $money = fn ($value) => $value === null ? 'Chưa xác nhận' : number_format($value / 100, 0, ',', '.').' đ';
    $displayDate = fn ($value) => \Illuminate\Support\Carbon::parse($value)->format('d/m/Y');
    $labels = ['net_revenue'=>'Doanh thu thuần đơn hoàn thành (hàng hóa + giao)', 'cogs'=>'Giá vốn hàng bán theo snapshot đơn', 'gross_profit'=>'Lợi nhuận gộp hàng hóa', 'shipping_income'=>'Phí giao hàng thu khách', 'gift_service_income'=>'Doanh thu dịch vụ kèm theo', 'unallocated_income'=>'Chênh lệch tổng đơn chưa phân bổ', 'shipping_cost_actual'=>'Chi phí vận chuyển theo bản ghi', 'packaging_cost_actual'=>'Chi phí đóng gói theo bản ghi', 'payment_gateway_fee_actual'=>'Phí thanh toán theo bản ghi', 'other_direct_cost'=>'Chi phí trực tiếp khác theo bản ghi', 'operating_expenses'=>'Chi phí vận hành đã nhập', 'cancelled_costs'=>'Chi phí đã ghi nhận của đơn hủy', 'profit_before_tax'=>'Lợi nhuận trước thuế theo chi phí nhập thủ công', 'estimated_tax'=>'Thuế TNDN dự tính', 'actual_tax'=>'Thuế TNDN thực tế đã xác nhận', 'estimated_indirect_tax'=>'VAT đầu ra tham khảo', 'actual_indirect_tax'=>'VAT thực tế đã xác nhận', 'net_profit_after_tax'=>'Lợi nhuận sau thuế TNDN thực tế', 'estimated_net_profit_after_tax'=>'Lợi nhuận sau thuế TNDN dự tính'];
    $rows = collect($actual['rows']);
    $knownCardIncome = $rows->sum(fn ($row) => $row['summary']['gift_card_income'] ?? 0);
    $knownWrapIncome = $rows->sum(fn ($row) => $row['summary']['gift_wrap_income'] ?? 0);
    $missingCardSnapshots = $rows->filter(fn ($row) => $row['summary']['gift_card_income'] === null)->count();
    $missingWrapSnapshots = $rows->filter(fn ($row) => $row['summary']['gift_wrap_income'] === null)->count();
@endphp
<h1 class="admin-page-title mb-4">Doanh thu & Lợi nhuận</h1>
<form class="mb-6 flex flex-wrap items-end gap-3 rounded-xl bg-white p-4 border" method="GET">
    <label>Kỳ<select name="period" class="block rounded-lg border-gray-300">
        @foreach(['today'=>'Hôm nay','yesterday'=>'Hôm qua','week'=>'Tuần này','last_week'=>'Tuần trước','month'=>'Tháng đã chọn','last_month'=>'Tháng trước','quarter'=>'Quý đã chọn','last_quarter'=>'Quý trước','year'=>'Năm nay / năm chọn','last_year'=>'Năm trước','custom'=>'Khoảng ngày','all'=>'Toàn thời gian'] as $key=>$label)<option value="{{ $key }}" @selected($period === $key)>{{ $label }}</option>@endforeach
    </select></label>
    @if(in_array($period, ['month', 'last_month', 'quarter', 'last_quarter', 'year', 'last_year'], true))<label>Mốc tháng / quý / năm<input class="block rounded-lg border-gray-300" type="date" name="period_date" value="{{ request('period_date', $to) }}"></label>@endif
    <label>Từ ngày<input class="block rounded-lg border-gray-300" type="date" name="from_date" value="{{ $from }}"></label>
    <label>Đến ngày<input class="block rounded-lg border-gray-300" type="date" name="to_date" value="{{ $to }}"></label>
    <label>Năm phân tích<input class="block w-28 rounded-lg border-gray-300" type="number" min="2000" max="2100" name="chart_year" value="{{ $chartYear }}"></label>
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
    <div class="mb-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl bg-rose-600 p-4 text-white"><p class="text-xs text-rose-100">Giá trị đơn ghi nhận theo trạng thái</p><p class="mt-1 text-2xl font-bold">{{ $money($actual['totals']['revenue']) }}</p><p class="mt-1 text-xs text-rose-100">{{ $actual['totals']['recognized_count'] }} đơn đã thanh toán, xác nhận hoặc hoàn thành; chưa đồng nghĩa đã thu đủ</p></div>
        <div class="rounded-xl bg-gray-900 p-4 text-white"><p class="text-xs text-gray-300">Giá trị đơn còn hiệu lực</p><p class="mt-1 text-2xl font-bold">{{ $money($actual['totals']['orders_value']) }}</p><p class="mt-1 text-xs text-gray-300">{{ $actual['totals']['orders_count'] }} đơn; đã loại đơn hủy, trả hàng và hoàn tiền</p></div>
        <div class="rounded-xl bg-amber-50 p-4 text-amber-900"><p class="text-xs text-amber-700">Giá trị chờ ghi nhận</p><p class="mt-1 text-2xl font-bold">{{ $money($actual['totals']['awaiting_revenue']) }}</p><p class="mt-1 text-xs text-amber-700">{{ $actual['totals']['awaiting_count'] }} đơn chưa thanh toán/xác nhận/hoàn thành</p></div>
        <div class="rounded-xl bg-emerald-700 p-4 text-white"><p class="text-xs text-emerald-100">Tiền đã thu theo Payment</p><p class="mt-1 text-2xl font-bold">{{ $money($collectedTotal) }}</p><p class="mt-1 text-xs text-emerald-100">Chỉ cộng giao dịch trạng thái thành công trong kỳ; không suy ra từ đơn đã xác nhận</p></div>
    </div>
    <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
        <div class="rounded-lg bg-gray-50 p-3"><p class="text-xs text-gray-500">Đơn hoàn thành</p><p class="mt-1 text-lg font-bold">{{ $dataCoverage['completed_orders'] }}</p></div>
        <div class="rounded-lg bg-gray-50 p-3"><p class="text-xs text-gray-500">Đơn dự kiến</p><p class="mt-1 text-lg font-bold">{{ $dataCoverage['forecast_orders'] }}</p></div>
        <div class="rounded-lg bg-gray-50 p-3"><p class="text-xs text-gray-500">Đơn hủy</p><p class="mt-1 text-lg font-bold">{{ $dataCoverage['cancelled_orders'] }}</p></div>
        <div class="rounded-lg bg-gray-50 p-3"><p class="text-xs text-gray-500">Giá vốn dòng hàng</p><p class="mt-1 text-lg font-bold">{{ $dataCoverage['items_with_cost'] }}/{{ $dataCoverage['items'] }}</p></div>
        <div class="rounded-lg bg-gray-50 p-3"><p class="text-xs text-gray-500">Đơn có chi phí nhập thủ công</p><p class="mt-1 text-lg font-bold">{{ $dataCoverage['orders_with_direct_costs'] }}/{{ $dataCoverage['orders'] }}</p></div>
        <div class="rounded-lg bg-gray-50 p-3"><p class="text-xs text-gray-500">Giá vốn sản phẩm</p><p class="mt-1 text-lg font-bold">{{ $dataCoverage['products_with_cost'] }}/{{ $dataCoverage['products'] }}</p></div>
    </div>
    <p class="mt-3 text-xs text-gray-500">Chi phí lấy từ cấu hình mặc định: {{ $dataCoverage['orders_with_default_costs'] }} đơn (chỉ là ước tính, không tính là chi phí thực tế). Bút toán vận hành đã nhập: {{ $dataCoverage['expenses'] }} · Hồ sơ thuế: {{ $dataCoverage['tax_records'] }}. Không có bản ghi chi phí nghĩa là chưa đối soát, không chứng minh khoản chi bằng 0.</p>
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
<p class="mb-4 text-sm text-gray-600">Giá trị đơn được đọc từ Order; tiền đã thu chỉ đọc từ Payment thành công. Hai số này có thể khác nhau, đặc biệt với COD chưa giao. Đơn hủy, trả hàng hoặc hoàn tiền không vào doanh thu ghi nhận và lượt bán hoàn tất. Lợi nhuận theo chi phí nhập thủ công được tách khỏi ước tính dùng cấu hình mặc định; chi phí vận hành không có bản ghi sẽ để thiếu dữ liệu. Hồ sơ thuế thực tế phải khớp kỳ lọc và đủ dữ liệu xác nhận.</p>
<section class="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-3" aria-label="Nguồn chi phí và lợi nhuận tạm tính">
    <div class="rounded-xl border bg-white p-4"><p class="text-sm text-gray-600">Chi phí đã ghi</p><p class="mt-2 text-xl font-bold">{{ $money($actual['totals']['recorded_costs_subtotal']) }}</p><p class="mt-1 text-xs text-gray-500">Giá vốn snapshot, chi phí đơn nhập thủ công và vận hành trong kỳ</p></div>
    <div class="rounded-xl border border-blue-100 bg-blue-50 p-4"><p class="text-sm text-blue-800">Chi phí theo cấu hình</p><p class="mt-2 text-xl font-bold">{{ $money($actual['totals']['configured_costs_estimate']) }}</p><p class="mt-1 text-xs text-blue-800">Chỉ tạm tính, chưa phải chi phí thực tế được xác nhận</p></div>
    <div class="rounded-xl border border-amber-100 bg-amber-50 p-4"><p class="text-sm text-amber-900">Lợi nhuận trước thuế tạm tính</p><p class="mt-2 text-xl font-bold">{{ $money($actual['totals']['provisional_profit_before_tax']) }}</p><p class="mt-1 text-xs text-amber-900">{{ $actual['totals']['cancelled_costs'] === null ? 'Chưa gồm chi phí đơn hủy chưa rõ' : 'Dùng chi phí đã ghi và chi phí mặc định ước tính' }}</p></div>
</section>
<h2 class="text-xl font-bold mb-3">Đơn hoàn thành · {{ $actual['totals']['order_count'] }} đơn</h2>
@if($actual['totals']['missing_orders'])<p class="mb-4 rounded-xl bg-amber-50 p-4 text-amber-900">Chưa đủ chi phí nhập thủ công để xác nhận lợi nhuận cho {{ $actual['totals']['missing_orders'] }} đơn. Các dòng dùng cấu hình mặc định được giữ riêng là ước tính; hệ thống không coi chúng là chi phí thực tế.</p>@endif
<details class="mb-6 rounded-xl border border-gray-200 bg-white p-4">
    <summary class="cursor-pointer font-semibold text-gray-800">Mở các chỉ tiêu tài chính chi tiết (doanh thu, chi phí, thuế và đối soát)</summary>
<div class="mt-4 grid grid-cols-1 md:grid-cols-3 xl:grid-cols-4 gap-4">
    @foreach($labels as $key=>$label)<div class="rounded-xl bg-white border border-gray-200 shadow-sm p-4"><p class="text-sm text-gray-500">{{ $label }}</p><p class="mt-2 text-xl font-bold">{{ $money($actual['totals'][$key]) }}</p></div>@endforeach
    <div class="rounded-xl bg-white border p-4"><p>Biên lợi nhuận sau thuế thực tế</p><p class="mt-2 text-xl font-bold">{{ $actual['totals']['profit_margin'] === null ? 'Chưa xác nhận' : $actual['totals']['profit_margin'].'%' }}</p></div>
    <div class="rounded-xl bg-blue-50 border border-blue-100 p-4"><p class="text-sm text-blue-800">Lợi nhuận trực tiếp ước tính theo cấu hình</p><p class="mt-2 text-xl font-bold text-blue-950">{{ $money($actual['totals']['estimated_direct_profit']) }}</p><p class="mt-1 text-xs text-blue-800">{{ $actual['totals']['estimated_order_count'] }} đơn có chi phí mặc định; chưa trừ chi phí vận hành chưa đối soát.</p></div>
</div>
</details>
<details class="mb-6 rounded-xl border border-gray-200 bg-white p-4">
    <summary class="cursor-pointer font-semibold text-gray-800">Chi tiết doanh thu dịch vụ kèm theo</summary>
    <div class="mt-3 grid gap-3 sm:grid-cols-2">
        <p class="rounded-lg bg-gray-50 p-3 text-sm">Thiệp: <strong>{{ $rows->isEmpty() || $missingCardSnapshots === $rows->count() ? 'Chưa xác nhận' : $money($knownCardIncome) }}</strong><span class="mt-1 block text-xs text-gray-500">{{ $rows->count() - $missingCardSnapshots }}/{{ $rows->count() }} đơn có snapshot riêng; không phân bổ ngược đơn lịch sử.</span></p>
        <p class="rounded-lg bg-gray-50 p-3 text-sm">Gói quà: <strong>{{ $rows->isEmpty() || $missingWrapSnapshots === $rows->count() ? 'Chưa xác nhận' : $money($knownWrapIncome) }}</strong><span class="mt-1 block text-xs text-gray-500">{{ $rows->count() - $missingWrapSnapshots }}/{{ $rows->count() }} đơn có snapshot riêng; không phân bổ ngược đơn lịch sử.</span></p>
    </div>
    <p class="mt-2 text-xs text-gray-500">Đơn lịch sử không lưu riêng khoản nào sẽ được ghi là chưa đủ dữ liệu; hệ thống không tự phân bổ tổng đơn.</p>
</details>
@if($actual['totals']['unallocated_income'] !== 0)
<p class="mb-6 rounded-xl bg-amber-50 p-4">Chênh lệch lịch sử chưa phân bổ: {{ $money($actual['totals']['unallocated_income']) }}. Checkout cũ gộp tiền thiệp/gói quà vào tổng đơn mà không lưu riêng, nên không thể tách ngược từng khoản nếu thiếu bằng chứng. Đơn mới sẽ lưu riêng phí thiệp và gói quà; khoản lịch sử này chưa được cộng vào lợi nhuận.</p>
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
        if ($metric['state'] === 'negative_base') return 'Không tính % vì kỳ trước âm';
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
<section class="mb-6 rounded-xl border border-rose-100 bg-white p-5 shadow-sm">
    <h2 class="text-lg font-bold">So sánh kỳ đã chọn với kỳ tương ứng trước đó</h2>
    <p class="mt-1 text-sm text-gray-600">{{ $periodComparison['current_label'] }} so với {{ $periodComparison['previous_label'] }}; kỳ đang diễn ra được so sánh đến cùng mốc ngày.</p>
    <div class="mt-4 grid gap-3 sm:grid-cols-2">
        @foreach(['revenue'=>'Doanh thu ghi nhận','profit'=>'Lợi nhuận trước thuế'] as $metricKey=>$metricLabel)
            @php $metric=$periodComparison[$metricKey]; @endphp
            <div class="rounded-lg bg-gray-50 p-4"><p class="text-sm text-gray-500">{{ $metricLabel }}</p><p class="mt-1 text-xl font-bold">{{ $money($metric['current']) }}</p><p class="text-xs text-gray-500">Kỳ trước: {{ $money($metric['previous']) }}</p><p class="mt-2 text-sm">Chênh lệch: {{ $money($metric['difference']) }} @if($metric['percentage'] !== null) · {{ number_format($metric['percentage'], 2, ',', '.') }}%@elseif($metric['state']==='new') · Mới phát sinh @elseif($metric['state']==='negative_base') · Không tính % vì kỳ trước âm @elseif($metric['state']==='unavailable') · Chưa đủ dữ liệu tài chính @endif</p></div>
        @endforeach
    </div>
</section>
@php
    $masterChartGroups = [
        'revenue_12m' => '1. Doanh thu theo 12 tháng',
        'month_comparison' => '2. So sánh doanh thu tháng này và tháng trước',
        'quarterly' => '3. Doanh thu, giá vốn, chi phí và lợi nhuận theo 4 quý',
        'yearly' => '4. Doanh thu, chi phí và lợi nhuận theo năm',
        'revenue_profit' => '5. Doanh thu và lợi nhuận theo tháng',
        'monthly_costs' => '6. So sánh các nhóm chi phí theo tháng',
        'profit_margin' => '7. Lợi nhuận và biên lợi nhuận',
        'best_sellers' => '8. Sản phẩm bán chạy',
        'payments' => '9. Doanh thu theo phương thức thanh toán',
        'cost_coverage' => '10. Tỷ trọng chi phí và độ bao phủ dữ liệu',
        'daily_revenue' => '11. Doanh thu theo chuỗi thời gian của kỳ đã chọn',
        'growth_comparisons' => '12. So sánh MoM, QoQ, YoY và kỳ đã chọn',
    ];
@endphp
<section id="finance-master-charts" class="mb-6 rounded-xl border border-rose-100 bg-white p-5 shadow-sm">
    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <div><h2 class="text-xl font-bold">Phân tích tài chính · 12 nhóm biểu đồ</h2><p class="mt-1 text-sm text-gray-600">Năm phân tích {{ $chartYear }} · cùng nguồn tổng hợp với bảng báo cáo. Nhấp vào cột theo tháng, quý hoặc năm để mở kỳ chi tiết.</p></div>
        <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-800">Kỳ doanh thu dùng ngày tạo đơn hiện có; dữ liệu cũ chưa có thời điểm hoàn tất riêng</span>
    </div>
    <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
        @foreach($masterChartGroups as $series => $title)
            <article class="min-w-0 rounded-lg border border-gray-200 p-4">
                <h3 class="min-h-10 font-semibold text-gray-900">{{ $title }}</h3>
                <div class="mt-2 h-56 overflow-x-auto"><div class="finance-breakdown-stage h-56"><canvas class="finance-breakdown-chart" data-chart="{{ $series }}" aria-label="{{ $title }}" role="img"></canvas></div></div>
                <p class="mt-2 min-h-8 text-xs text-gray-500" data-breakdown-tooltip="{{ $series }}">Di chuột để xem số tiền, trạng thái dữ liệu và kỳ đối chiếu.</p>
                @if($series === 'growth_comparisons')
        <p class="text-xs text-gray-500">Tỷ lệ % chỉ hiện khi kỳ trước có giá trị dương; kỳ trước bằng 0 được ghi là phát sinh mới, còn kỳ trước âm không tính phần trăm.</p>
                @endif
            </article>
        @endforeach
    </div>
    <div class="mt-5 grid gap-4 xl:grid-cols-2">
        <article class="rounded-lg border p-4"><h3 class="font-semibold">Doanh thu theo danh mục</h3><p class="mt-1 text-xs text-gray-500">Doanh thu hàng trước phân bổ giảm giá ở cấp đơn; không đại diện số thuế hay tiền đã thu.</p><div class="mt-3 space-y-2">@forelse($categoryBreakdown as $row)<div class="flex justify-between gap-3 border-b pb-2 text-sm"><span>{{ $row->category_name }} · {{ $row->quantity }} sản phẩm / {{ $row->order_count }} đơn</span><strong>{{ number_format((float) $row->sales_total, 0, ',', '.') }} đ</strong></div>@empty<p class="text-sm text-gray-500">Chưa có đơn hoàn thành trong kỳ.</p>@endforelse</div></article>
        <article class="rounded-lg border p-4"><h3 class="font-semibold">Số đơn theo trạng thái</h3><p class="mt-1 text-xs text-gray-500">Bao gồm cả đơn đang xử lý và đơn hủy để đối soát quy trình; đơn hủy không vào doanh thu ghi nhận.</p><div class="mt-3 space-y-2">@forelse($ordersByStatus as $row)<div class="flex justify-between gap-3 border-b pb-2 text-sm"><span>{{ $row->order_status ?: 'Chưa phân trạng thái' }}</span><strong>{{ $row->order_count }} đơn</strong></div>@empty<p class="text-sm text-gray-500">Không có đơn trong kỳ.</p>@endforelse</div><p class="mt-3 text-xs text-gray-500">Giá trị đơn trung bình: {{ number_format($averageOrderValue / 100, 0, ',', '.') }} đ / đơn không hủy.</p></article>
    </div>
</section>
<section class="mb-6 grid gap-6 xl:grid-cols-2">
    @foreach(['weekly' => ['Doanh thu & lợi nhuận theo tuần', 'Tuần theo lịch; các cột có nhãn mốc thời gian rõ ràng.'], 'monthly' => ['Doanh thu & lợi nhuận theo tháng', 'Tháng theo lịch; các cột được chia đều.'], 'quarterly' => ['Doanh thu & lợi nhuận theo quý', 'Quý theo lịch, một quý gồm ba tháng.'], 'yearly' => ['Doanh thu & lợi nhuận theo năm', 'Năm theo lịch; năm hiện tại hiển thị dữ liệu đến ngày hôm nay.']] as $series => [$title, $subtitle])
        <article class="min-w-0 rounded-xl border bg-white p-5">
            <h2 class="text-lg font-bold">{{ $title }}</h2>
            <p class="mt-1 text-sm text-gray-600">{{ $subtitle }}</p>
            <div class="mt-4 overflow-x-auto"><div class="h-72 min-w-full finance-comparison-stage"><canvas class="finance-comparison-chart" data-series="{{ $series }}" aria-label="{{ $title }}" role="img"></canvas></div></div>
            <p class="mt-3 text-xs text-gray-500" data-finance-comparison-tooltip="{{ $series }}">Di chuột trên biểu đồ để xem chi tiết từng kỳ.</p>
            <div class="mt-4 max-h-56 overflow-y-auto rounded-lg border border-gray-100">
                <table class="w-full text-left text-xs">
                    <thead class="sticky top-0 bg-gray-50 text-gray-600"><tr><th class="p-2">Kỳ</th><th class="p-2">Doanh thu</th><th class="p-2">Lợi nhuận</th><th class="p-2">Biên</th></tr></thead>
                    <tbody>
                        @forelse($comparison[$series . '_trend'] as $row)
                            <tr class="border-t border-gray-100"><td class="p-2 font-medium text-gray-800">{{ $row['label'] }}</td><td class="p-2">{{ $money($row['revenue']) }}</td><td class="p-2">{{ $money($row['profit']) }}</td><td class="p-2">{{ $row['margin'] === null ? 'Chưa xác nhận' : number_format($row['margin'], 2, ',', '.') . '%' }}</td></tr>
                        @empty
                            <tr class="border-t border-gray-100"><td colspan="4" class="p-3 text-gray-500">Chưa có đơn hoàn thành để lập xu hướng.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    @endforeach
</section>
<section class="mb-6 grid gap-6 xl:grid-cols-3">
    <article class="min-w-0 rounded-xl border bg-white p-5"><h2 class="font-bold">Thanh toán và số tiền đã thu</h2><p class="mt-1 text-xs text-gray-500">Đã thu dựa trên trạng thái giao dịch; phí chỉ cộng khi có chi phí thực tế đã ghi.</p><div class="mt-3 h-56 overflow-x-auto"><div class="finance-breakdown-stage h-56"><canvas class="finance-breakdown-chart" data-chart="payments" aria-label="Tiền đã thu và đang chờ theo phương thức thanh toán" role="img"></canvas></div></div><p class="mt-2 text-xs text-gray-500" data-breakdown-tooltip="payments">Di chuột để xem số tiền và trạng thái từng phương thức.</p><div class="mt-3 overflow-x-auto"><table class="w-full text-sm"><thead><tr><th class="p-2 text-left">Phương thức</th><th class="p-2 text-right">Đơn</th><th class="p-2 text-right">Đã thu</th><th class="p-2 text-right">Chờ thu</th><th class="p-2 text-right">Phí thực tế đã ghi</th></tr></thead><tbody>@forelse($paymentBreakdown as $row)<tr class="border-t"><td class="p-2">{{ strtoupper($row->payment_method) }}</td><td class="p-2 text-right">{{ $row->order_count }}</td><td class="p-2 text-right">{{ number_format((float) $row->collected_amount, 0, ',', '.') }} đ</td><td class="p-2 text-right">{{ number_format((float) $row->awaiting_amount, 0, ',', '.') }} đ</td><td class="p-2 text-right">{{ number_format((float) $row->actual_fees, 0, ',', '.') }} đ <span class="text-xs text-gray-500">({{ $row->orders_with_known_fees }} đơn có dữ liệu)</span></td></tr>@empty<tr><td colspan="5" class="p-3 text-gray-500">Chưa có dữ liệu.</td></tr>@endforelse</tbody></table></div></article>
    <article class="min-w-0 rounded-xl border bg-white p-5"><h2 class="font-bold">Chi phí vận hành theo nhóm</h2><div class="mt-3 h-56 overflow-x-auto"><div class="finance-breakdown-stage h-56"><canvas class="finance-breakdown-chart" data-chart="expenses" aria-label="Chi phí vận hành theo nhóm" role="img"></canvas></div></div><p class="mt-2 text-xs text-gray-500" data-breakdown-tooltip="expenses">Di chuột để xem tỷ trọng chi theo nhóm.</p><div class="mt-3 space-y-2">@forelse($expenseBreakdown as $row)<div class="flex justify-between gap-2 border-b pb-2 text-sm"><span>{{ ucfirst(str_replace('_', ' ', $row->expense_type)) }} · {{ $row->entry_count }} khoản</span><strong>{{ number_format((float) $row->total_amount, 0, ',', '.') }} đ</strong></div>@empty<p class="text-sm text-gray-500">Kỳ này chưa ghi nhận khoản chi vận hành.</p>@endforelse</div></article>
    <article class="min-w-0 rounded-xl border bg-white p-5"><h2 class="font-bold">Sản phẩm bán nhiều nhất</h2><div class="mt-3 h-56 overflow-x-auto"><div class="finance-breakdown-stage h-56"><canvas class="finance-breakdown-chart" data-chart="products" aria-label="Số lượng sản phẩm đã bán" role="img"></canvas></div></div><p class="mt-2 text-xs text-gray-500" data-breakdown-tooltip="products">Chỉ tính đơn hoàn thành hoặc đã giao.</p><div class="mt-3 space-y-2">@forelse($completedBestSellers as $row)<div class="flex justify-between gap-2 border-b pb-2 text-sm"><span>{{ $row->product_name }} · {{ $row->quantity }} sản phẩm</span><strong>{{ number_format((float) $row->sales_total, 0, ',', '.') }} đ</strong></div>@empty<p class="text-sm text-gray-500">Chưa có đơn hoàn thành trong kỳ.</p>@endforelse</div></article>
</section>
<div class="mb-6 rounded-xl bg-white border p-5">
    <h2 class="text-lg font-bold mb-3">Xu hướng theo ngày tạo đơn hoàn thành</h2>
    <p class="text-sm mb-3">Lợi nhuận trên biểu đồ là lợi nhuận trực tiếp trước chi phí vận hành và thuế. Khoảng trống thể hiện thiếu dữ liệu.</p>
    <div class="h-72"><canvas id="finance-chart" aria-label="Doanh thu, giá vốn, chi phí và lợi nhuận theo ngày" role="img"></canvas></div>
    <div class="overflow-x-auto mt-4"><table class="w-full text-sm text-left"><thead><tr><th class="p-2">Ngày</th><th>Hàng hóa + giao</th><th>Thu thiệp</th><th>Thu gói quà</th><th>Chênh lệch chưa phân bổ</th><th>Giá vốn</th><th>Chi phí trực tiếp khác</th><th>Lợi nhuận trực tiếp</th></tr></thead><tbody>
    @forelse($actual['daily'] as $date=>$day)
        @php $direct = in_array(null, array_intersect_key($day, array_flip(\App\Services\FinanceAnalyticsService::COST_FIELDS)), true) ? null : array_sum(array_intersect_key($day, array_flip(\App\Services\FinanceAnalyticsService::COST_FIELDS))); @endphp
        <tr class="border-t"><td class="p-2">{{ $displayDate($date) }}</td><td>{{ $money($day['net_product_revenue'] + $day['shipping_income']) }}</td><td>{{ $money($day['gift_card_income']) }}</td><td>{{ $money($day['gift_wrap_income']) }}</td><td>{{ $money($day['unallocated_income']) }}</td><td>{{ $money($day['cogs']) }}</td><td>{{ $money($direct) }}</td><td>{{ $money($day['order_profit']) }}</td></tr>
    @empty<tr><td colspan="8" class="p-4">Chưa có đơn hoàn thành trong kỳ.</td></tr>@endforelse
    </tbody></table></div>
</div>
<h2 class="text-xl font-bold mb-3">Dự kiến · {{ $forecast['totals']['order_count'] }} đơn chưa hoàn thành</h2>
<div class="grid md:grid-cols-3 gap-4 mb-6">
    @foreach(['net_revenue'=>'Doanh thu thuần dự kiến', 'cogs'=>'Giá vốn dự kiến', 'order_profit'=>'Lợi nhuận trực tiếp dự kiến'] as $key=>$label)<div class="rounded-xl bg-white border p-4"><p>{{ $label }}</p><strong>{{ $money($forecast['totals'][$key]) }}</strong></div>@endforeach
</div>
<div class="rounded-xl bg-white border p-5 overflow-x-auto"><h2 class="text-lg font-bold mb-3">Đối chiếu các đơn hoàn thành</h2><table class="w-full text-sm text-left"><thead><tr><th>Đơn hàng</th><th>Hàng hóa + giao</th><th>Thu thiệp</th><th>Thu gói quà</th><th>Chênh lệch chưa phân bổ</th><th>Giá vốn</th><th>Chi phí trực tiếp khác</th><th>Lợi nhuận trực tiếp</th><th>Dữ liệu</th></tr></thead><tbody>
@forelse($actual['rows'] as $row)
@php $directOther = in_array(null, array_intersect_key($row['summary'], array_flip(\App\Services\FinanceAnalyticsService::COST_FIELDS)), true) ? null : array_sum(array_intersect_key($row['summary'], array_flip(\App\Services\FinanceAnalyticsService::COST_FIELDS))); @endphp
<tr class="border-t"><td class="py-3"><a class="text-rose-600" href="{{ route('admin.finance.order', $row['order']) }}">{{ $row['order']->order_code }}</a></td><td>{{ $money($row['summary']['net_product_revenue'] + $row['summary']['shipping_income']) }}</td><td>{{ $money($row['summary']['gift_card_income']) }}</td><td>{{ $money($row['summary']['gift_wrap_income']) }}</td><td>{{ $money($row['summary']['unallocated_income']) }}</td><td>{{ $money($row['summary']['cogs']) }}</td><td>{{ $money($directOther) }}</td><td>{{ $money($row['summary']['order_profit']) }}</td><td>{{ $row['summary']['data_status'] }}</td></tr>
@empty<tr><td class="py-4" colspan="9">Chưa có dữ liệu.</td></tr>@endforelse
</tbody></table></div>
<p class="mt-6 text-sm text-gray-500">Các chỉ số thuế được tính từ cấu hình và dữ liệu do người quản trị cung cấp. Nghĩa vụ thuế chính thức cần được đối chiếu với quy định pháp luật hiện hành và hồ sơ kế toán thực tế.</p>
<script type="application/json" id="finance-series">{!! json_encode($actual['daily'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
<script type="application/json" id="finance-breakdown-series">{!! json_encode(array_merge($breakdownCharts, $analyticsCharts), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
<script type="application/json" id="finance-comparison-series">{!! json_encode(['weekly' => $comparison['weekly_trend'], 'monthly' => $comparison['monthly_trend'], 'quarterly' => $comparison['quarterly_trend'], 'yearly' => $comparison['yearly_trend']], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
<script src="{{ asset('js/finance-chart.js') }}" defer></script>
        <script src="{{ asset('js/finance-comparison-chart.js') }}" defer></script>
        <script src="{{ asset('js/finance-breakdown-chart.js') }}" defer></script>
@endsection
