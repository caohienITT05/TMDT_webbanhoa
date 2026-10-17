@extends('layouts.admin')
@section('title', 'Tổng quan tài chính')
@section('content')
@include('admin.finance.nav')
@php
    $money = fn ($value) => $value === null ? 'Chưa xác nhận' : number_format($value / 100, 0, ',', '.') . ' đ';
    $displayDate = fn ($value) => \Illuminate\Support\Carbon::parse($value)->format('d/m/Y');
    $totals = $actual['totals'];
    $periodNames = ['today'=>'Hôm nay','yesterday'=>'Hôm qua','week'=>'Tuần này','last_week'=>'Tuần trước','month'=>'Tháng đã chọn','last_month'=>'Tháng trước','quarter'=>'Quý đã chọn','last_quarter'=>'Quý trước','year'=>'Năm đã chọn','last_year'=>'Năm trước','custom'=>'Khoảng ngày','all'=>'Toàn thời gian'];
    $periodLabel = ($periodNames[$period] ?? 'Kỳ đã chọn') . ' · ' . $displayDate($from) . ' – ' . $displayDate($to);
    $totalCosts = $totals['costs_shown_subtotal'];
    $verifiedProfit = $totals['profit_before_tax'];
    $profitShown = $verifiedProfit ?? $totals['estimated_profit_before_tax'] ?? $totals['provisional_profit_before_tax'];
    $profitLabel = match (true) {
        $verifiedProfit !== null => 'Lợi nhuận trước thuế đã đối chiếu theo dữ liệu hiện có',
        $totals['estimated_profit_before_tax'] !== null => 'Tạm tính với chi phí cấu hình, chưa xác nhận chứng từ',
        $totals['provisional_profit_before_tax'] !== null => $totals['cancelled_costs'] === null
            ? 'Tạm tính; chưa gồm chi phí đơn hủy chưa rõ'
            : 'Tạm tính với chi phí đã ghi và chi phí cấu hình',
        default => 'Chưa đủ dữ liệu để tính lợi nhuận',
    };
    $citLabel = $taxCompliance['cit']['status_label'] ?? 'Chưa cấu hình hồ sơ thuế';
    $vatLabel = $totals['estimated_indirect_tax'] === null ? 'VAT chưa đủ phân loại sản phẩm' : 'VAT đầu ra tham khảo: ' . $money($totals['estimated_indirect_tax']);
    $changeLabel = function (array $metric, string $kind = 'revenue') use ($money): string {
        if ($metric['state'] === 'unavailable') return 'Chưa đủ dữ liệu để so sánh';
        if ($kind === 'profit' && $metric['previous'] < 0 && $metric['current'] >= 0) return 'Chuyển từ lỗ sang lãi · chênh lệch ' . $money($metric['difference']);
        if ($kind === 'profit' && $metric['previous'] >= 0 && $metric['current'] < 0) return 'Chuyển từ lãi sang lỗ · chênh lệch ' . $money($metric['difference']);
        if ($metric['state'] === 'new') return 'Mới phát sinh · chênh lệch ' . $money($metric['difference']);
        if ($metric['state'] === 'negative_base') return 'Kỳ trước âm · chênh lệch ' . $money($metric['difference']) . ', không tính phần trăm';
        $direction = $metric['state'] === 'up' ? 'Tăng' : ($metric['state'] === 'down' ? 'Giảm' : 'Không đổi');
        return $direction . ' ' . number_format((float) $metric['percentage'], 2, ',', '.') . '% · chênh lệch ' . $money($metric['difference']);
    };
@endphp

<header class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div><h1 class="admin-page-title">Tổng quan tài chính</h1><p class="mt-1 text-sm text-gray-600">{{ $periodLabel }} · số liệu lấy từ đơn hàng, thanh toán và chi phí đã ghi.</p></div>
    <a href="{{ route('admin.finance.export', ['from_date' => $from, 'to_date' => $to]) }}" class="rounded-lg border border-rose-200 bg-white px-4 py-2 text-sm font-semibold text-rose-700">Xuất CSV kỳ này</a>
</header>

<form class="mb-5 flex flex-wrap items-end gap-3 rounded-xl border bg-white p-4" method="GET">
    <label class="text-sm font-medium">Kỳ<select name="period" class="mt-1 block rounded-lg border-gray-300">
        @foreach($periodNames as $key => $label)<option value="{{ $key }}" @selected($period === $key)>{{ $label }}</option>@endforeach
    </select></label>
    @if(in_array($period, ['month','last_month','quarter','last_quarter','year','last_year'], true))<label class="text-sm font-medium">Mốc kỳ<input class="mt-1 block rounded-lg border-gray-300" type="date" name="period_date" value="{{ request('period_date', $to) }}"></label>@endif
    @if($period === 'custom')<label class="text-sm font-medium">Từ ngày<input class="mt-1 block rounded-lg border-gray-300" type="date" name="from_date" value="{{ $from }}"></label><label class="text-sm font-medium">Đến ngày<input class="mt-1 block rounded-lg border-gray-300" type="date" name="to_date" value="{{ $to }}"></label>@endif
    <label class="text-sm font-medium">Năm biểu đồ<input class="mt-1 block w-28 rounded-lg border-gray-300" type="number" min="2000" max="2100" name="chart_year" value="{{ $chartYear }}"></label>
    <button class="rounded-lg bg-rose-700 px-5 py-2 font-semibold text-white">Xem báo cáo</button>
</form>

<section aria-label="Sáu chỉ số tài chính chính" class="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
    <article class="rounded-xl border border-rose-100 bg-white p-4 shadow-sm"><p class="text-sm text-gray-600">Doanh thu ghi nhận</p><p class="mt-2 text-2xl font-bold text-gray-950">{{ $money($totals['revenue']) }}</p><p class="mt-1 text-xs text-gray-500">{{ $totals['recognized_count'] }} đơn đã ghi nhận · {{ $periodLabel }}</p></article>
    <article class="rounded-xl border border-emerald-100 bg-white p-4 shadow-sm"><p class="text-sm text-gray-600">Tiền đã thu</p><p class="mt-2 text-2xl font-bold text-gray-950">{{ $money($collectedTotal) }}</p><p class="mt-1 text-xs text-gray-500">Chỉ giao dịch thanh toán thành công</p></article>
    <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm"><p class="text-sm text-gray-600">Chi phí đã ghi và tạm tính</p><p class="mt-2 text-2xl font-bold text-gray-950">{{ $money($totalCosts) }}</p><p class="mt-1 text-xs text-gray-500">{{ $totals['recorded_costs_subtotal'] === null ? 'Chưa có giá vốn hoặc chi phí đã nhập trong kỳ' : $money($totals['recorded_costs_subtotal']).' từ giá vốn, chi phí nhập và vận hành' }} @if($totals['configured_costs_estimate'] !== null) · {{ $money($totals['configured_costs_estimate']) }} từ cấu hình mặc định @endif @if($totals['costs_shown_incomplete']) · chưa đủ chi phí xác nhận @endif</p></article>
    <article class="rounded-xl border border-amber-100 bg-white p-4 shadow-sm"><p class="text-sm text-gray-600">Lợi nhuận trước thuế / tạm tính</p><p class="mt-2 text-2xl font-bold text-gray-950">{{ $money($profitShown) }}</p><p class="mt-1 text-xs text-gray-500">{{ $profitLabel }}</p></article>
    <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm"><p class="text-sm text-gray-600">Đơn hoàn thành</p><p class="mt-2 text-2xl font-bold text-gray-950">{{ $totals['order_count'] }}</p><p class="mt-1 text-xs text-gray-500">Đã giao hoặc hoàn tất trong kỳ báo cáo</p></article>
    <article class="rounded-xl border border-blue-100 bg-white p-4 shadow-sm"><p class="text-sm text-gray-600">Thuế và tình trạng hồ sơ</p><p class="mt-2 text-base font-bold text-gray-950">{{ $citLabel }}</p><p class="mt-1 text-xs text-gray-500">{{ $vatLabel }}</p><a href="{{ route('admin.finance.taxes') }}" class="mt-2 inline-block text-xs font-semibold text-rose-700 underline">Xem thông tin thuế</a></article>
</section>

<section class="mb-5 grid gap-3 lg:grid-cols-2">
    @foreach(['revenue' => ['Doanh thu', $periodComparison['revenue']], 'profit' => ['Lợi nhuận trước thuế', $periodComparison['profit']]] as $key => [$label, $metric])
        <article class="rounded-xl border bg-white p-4">
            <h2 class="font-semibold text-gray-900">{{ $label }} so với kỳ liền trước</h2>
            <p class="mt-2 text-sm text-gray-600">{{ $periodComparison['current_label'] }}: <strong class="text-gray-900">{{ $money($metric['current']) }}</strong></p>
            <p class="text-sm text-gray-600">{{ $periodComparison['previous_label'] }}: <strong class="text-gray-900">{{ $money($metric['previous']) }}</strong></p>
            <p class="mt-2 text-sm font-semibold {{ $metric['state'] === 'up' ? 'text-emerald-700' : ($metric['state'] === 'down' ? 'text-red-700' : 'text-gray-700') }}">{{ $changeLabel($metric, $key) }}</p>
        </article>
    @endforeach
</section>

<section class="mb-5 grid gap-5 xl:grid-cols-[minmax(0,1.5fr)_minmax(18rem,.8fr)]">
    <article class="overflow-hidden rounded-xl border bg-white p-4 sm:p-5">
        <div class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="text-lg font-bold">Doanh thu 12 tháng</h2><p class="mt-1 text-sm text-gray-600">Các cột chia đều theo tháng; chọn cột để mở kỳ chi tiết.</p></div><a href="{{ route('admin.finance.revenue', ['period' => 'year', 'period_date' => $to, 'chart_year' => $chartYear]) }}" class="text-sm font-semibold text-rose-700 underline">Mở báo cáo doanh thu</a></div>
        <div class="mt-3 overflow-x-auto"><div class="finance-breakdown-stage h-60"><canvas class="finance-breakdown-chart" data-chart="revenue_12m" aria-label="Doanh thu theo 12 tháng" role="img"></canvas></div></div>
        <p class="mt-2 text-xs text-gray-500" data-breakdown-tooltip="revenue_12m">Di chuột để xem số liệu từng tháng.</p>
    </article>
    <article class="rounded-xl border bg-white p-4 sm:p-5">
        <div class="flex items-start justify-between gap-3"><div><h2 class="text-lg font-bold">Sản phẩm được đặt nhiều trong kỳ</h2><p class="mt-1 text-sm text-gray-600">Tổng quantity từ đơn còn hiệu lực, gồm đơn đang xử lý và đã hoàn tất; không phải doanh thu đã ghi nhận.</p></div><a href="{{ route('admin.finance.revenue', ['period' => $period, 'period_date' => $to]) }}" class="text-sm font-semibold text-rose-700 underline">Chi tiết</a></div>
        <ol class="mt-4 space-y-3">
            @forelse($bestSellers->take(5) as $index => $product)
                <li class="flex items-center justify-between gap-3 border-b border-gray-100 pb-2"><span class="min-w-0"><span class="mr-2 text-xs text-gray-400">{{ $index + 1 }}.</span><span class="font-medium text-gray-800">{{ $product->product_name }}</span></span><strong class="shrink-0 text-sm">{{ $product->quantity }} sản phẩm</strong></li>
            @empty<li class="text-sm text-gray-500">Kỳ này chưa có đơn còn hiệu lực.</li>@endforelse
        </ol>
    </article>
</section>

<aside class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
    <p>Độ bao phủ chi phí: {{ $totals['missing_orders'] }} đơn chưa đủ chi phí xác nhận · {{ $dataCoverage['items'] - $dataCoverage['items_with_cost'] }} dòng hàng thiếu snapshot giá vốn. Chi tiết đối soát nằm riêng.</p>
    <a href="{{ route('admin.finance.reconciliation', ['period' => $period, 'period_date' => $to]) }}" class="shrink-0 font-semibold underline">Mở đối soát</a>
</aside>

<script type="application/json" id="finance-breakdown-series">{!! json_encode(['revenue_12m' => $analyticsCharts['revenue_12m']], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
<script src="{{ asset('js/finance-breakdown-chart.js') }}" defer></script>
@endsection
