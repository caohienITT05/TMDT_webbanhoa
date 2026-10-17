<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\FinanceAnalyticsService;
use App\Services\TaxComplianceService;
use App\Support\FinanceDateRange;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FinanceController extends Controller
{
    private function money(bool $required = false): array
    {
        return [$required ? 'required' : 'nullable', 'numeric', 'min:0', 'max:9999999999999.99', 'decimal:0,2'];
    }

    private function activeOrderPredicate(string $alias = 'o'): string
    {
        $conditions = [];
        foreach (['order_status', 'status', 'payment_status'] as $field) {
            if (Schema::hasColumn('orders', $field)) {
                $statuses = "'" . implode("','", Order::SALES_INVALID_STATUSES) . "'";
                $conditions[] = "LOWER(COALESCE({$alias}.{$field}, '')) NOT IN ({$statuses})";
            }
        }

        return $conditions ? implode(' AND ', $conditions) : '1 = 1';
    }

    private function completedOrderPredicate(string $alias = 'o'): string
    {
        $statuses = [];
        if (Schema::hasColumn('orders', 'order_status')) {
            $statuses[] = "NULLIF({$alias}.order_status, '')";
        }
        if (Schema::hasColumn('orders', 'status')) {
            $statuses[] = "{$alias}.status";
        }

        return 'LOWER(COALESCE('.implode(', ', $statuses ?: ['NULL']).", '')) IN ('completed','delivered')";
    }

    public function index(Request $request, FinanceAnalyticsService $finance)
    {
        return $this->dashboard($request, $finance, 'index');
    }

    public function revenue(Request $request, FinanceAnalyticsService $finance)
    {
        return $this->dashboard($request, $finance, 'revenue');
    }

    public function reconciliation(Request $request, FinanceAnalyticsService $finance)
    {
        return $this->dashboard($request, $finance, 'reconciliation');
    }

    private function dashboard(Request $request, FinanceAnalyticsService $finance, string $page)
    {
        $input = $request->validate([
            'period' => ['nullable', Rule::in(['today', 'yesterday', 'week', 'last_week', 'month', 'last_month', 'quarter', 'last_quarter', 'year', 'last_year', 'custom', 'all'])],
            'period_date' => ['nullable', 'date_format:Y-m-d'],
            'chart_year' => ['nullable', 'integer', 'between:2000,2100'],
            'from_date' => ['required_if:period,custom', 'nullable', 'date_format:Y-m-d'],
            'to_date' => ['required_if:period,custom', 'nullable', 'date_format:Y-m-d', 'after_or_equal:from_date'],
        ]);
        $today = CarbonImmutable::now(config('app.display_timezone', 'Asia/Ho_Chi_Minh'))->startOfDay();
        $period = $input['period'] ?? 'year';
        $anchor = isset($input['period_date']) ? CarbonImmutable::parse($input['period_date'], config('app.display_timezone', 'Asia/Ho_Chi_Minh')) : $today;
        $activityDates = array_filter([
            DB::table('orders')->min('created_at'),
            DB::table('expenses')->min('expense_date'),
        ]);
        $allStart = $activityDates ? CarbonImmutable::parse(min($activityDates), 'UTC')->setTimezone(config('app.display_timezone', 'Asia/Ho_Chi_Minh'))->startOfDay() : $today;
        $from = match ($period) {
            'today' => $today,
            'yesterday' => $today->subDay(),
            'week' => $today->startOfWeek(),
            'last_week' => $today->subWeek()->startOfWeek(),
            'month' => $anchor->startOfMonth(),
            'last_month' => $anchor->subMonthNoOverflow()->startOfMonth(),
            'quarter' => $anchor->startOfQuarter(),
            'last_quarter' => $anchor->subQuarterNoOverflow()->startOfQuarter(),
            'year' => $anchor->startOfYear(),
            'last_year' => $anchor->subYear()->startOfYear(),
            'custom' => CarbonImmutable::parse($input['from_date'], config('app.display_timezone', 'Asia/Ho_Chi_Minh')),
            'all' => $allStart,
            default => $today->startOfYear(),
        };
        $to = match ($period) {
            'today' => $today,
            'yesterday' => $today->subDay(),
            'week' => $today,
            'last_week' => $today->startOfWeek()->subDay(),
            'month' => $anchor->startOfMonth()->greaterThan($today) ? $anchor->endOfMonth() : $anchor->endOfMonth()->min($today),
            'last_month' => $anchor->subMonthNoOverflow()->endOfMonth(),
            'quarter' => $anchor->startOfQuarter()->greaterThan($today) ? $anchor->endOfQuarter() : $anchor->endOfQuarter()->min($today),
            'last_quarter' => $anchor->subQuarterNoOverflow()->endOfQuarter(),
            'year' => $anchor->startOfYear()->greaterThan($today) ? $anchor->endOfYear() : $anchor->endOfYear()->min($today),
            'last_year' => $anchor->subYear()->endOfYear(),
            'custom' => CarbonImmutable::parse($input['to_date'], config('app.display_timezone', 'Asia/Ho_Chi_Minh')),
            'all' => $today,
            default => $today,
        };
        $from = $from->toDateString();
        $to = $to->toDateString();
        $chartYear = (int) ($input['chart_year'] ?? $anchor->year);
        $actual = $finance->getPeriodSummary($from, $to);
        $forecast = $finance->getPeriodSummary($from, $to, true);
        [$queryFrom, $queryTo] = FinanceDateRange::utcBounds($from, $to);
        $periodOrders = DB::table('orders')
            ->where('created_at', '>=', $queryFrom)
            ->where('created_at', '<', $queryTo);
        $orderIds = (clone $periodOrders)->pluck('id');
        $itemCount = DB::table('order_items')->whereIn('order_id', $orderIds)->count();
        $dataCoverage = [
            'orders' => (clone $periodOrders)->count(),
            'completed_orders' => $actual['totals']['order_count'],
            'forecast_orders' => $forecast['totals']['order_count'],
            'cancelled_orders' => (clone $periodOrders)->where('order_status', 'cancelled')->count(),
            'items' => $itemCount,
            'items_with_cost' => DB::table('order_items')->whereIn('order_id', $orderIds)->whereNotNull('cost_price_snapshot')->count(),
            'orders_with_direct_costs' => DB::table('order_costs')->whereIn('order_id', $orderIds)->where('source', 'manual')->count(),
            'orders_with_default_costs' => DB::table('order_costs')->whereIn('order_id', $orderIds)->where('source', 'system_default')->count(),
            'expenses' => DB::table('expenses')->whereBetween('expense_date', [$from, $to])->count(),
            'tax_records' => DB::table('tax_records')->where('period_start', '<=', $to)->where('period_end', '>=', $from)->count(),
            'products' => DB::table('products')->count(),
            'products_with_cost' => DB::table('products')->whereNotNull('cost_price')->count(),
        ];
        $taxCompliance = app(TaxComplianceService::class)->summary();
        $rangeStart = CarbonImmutable::parse($from, config('app.display_timezone', 'Asia/Ho_Chi_Minh'));
        $rangeEnd = CarbonImmutable::parse($to, config('app.display_timezone', 'Asia/Ho_Chi_Minh'));
        $rangeEndExclusive = $rangeEnd->addDay()->startOfDay();
        [$queryFrom, $queryTo] = FinanceDateRange::utcBounds($from, $to);
        $firstCompletedOrder = Order::query()->where('created_at', '>=', $queryFrom)->where('created_at', '<', $queryTo)
            ->completedOrDelivered()->min('created_at');
        $trendFrom = $firstCompletedOrder ? CarbonImmutable::parse($firstCompletedOrder, 'UTC')->setTimezone(config('app.display_timezone', 'Asia/Ho_Chi_Minh'))->max($rangeStart) : $rangeEnd;
        $comparison = $finance->comparisonDashboard($today, $trendFrom, $rangeEnd);
        $periodComparison = $finance->compareSelectedPeriod($from, $to, $period);
        $chartDashboard = $finance->chartDashboard($chartYear, $anchor, $today);
        $latestPayments = DB::table('payments')->selectRaw('MAX(id) as id, order_id')->groupBy('order_id');
        $orderAmountSql = Schema::hasColumn('orders', 'total_amount')
            ? 'COALESCE(NULLIF(o.total_amount,0),o.total,0)'
            : 'COALESCE(o.total,0)';
        $paymentBreakdown = DB::table('orders as o')
            ->leftJoinSub($latestPayments, 'latest_payment', fn ($join) => $join->on('latest_payment.order_id', '=', 'o.id'))
            ->leftJoin('payments as p', 'p.id', '=', 'latest_payment.id')
            ->leftJoin('order_costs as oc', 'oc.order_id', '=', 'o.id')
            ->where('o.created_at', '>=', $queryFrom)->where('o.created_at', '<', $queryTo)
            ->whereRaw($this->activeOrderPredicate('o'))
            ->groupBy('o.payment_method')->orderBy('o.payment_method')
            ->get(['o.payment_method', DB::raw('COUNT(DISTINCT o.id) as order_count'),
                DB::raw("SUM(CASE WHEN LOWER(COALESCE(p.status, '')) IN ('completed','paid') THEN COALESCE(p.amount,{$orderAmountSql}) ELSE 0 END) as collected_amount"),
                DB::raw("SUM(CASE WHEN LOWER(COALESCE(p.status, '')) IN ('completed','paid') THEN 0 ELSE {$orderAmountSql} END) as awaiting_amount"),
                DB::raw('SUM(COALESCE(oc.payment_gateway_fee_actual,0)) as actual_fees'),
                DB::raw('COUNT(DISTINCT CASE WHEN oc.payment_gateway_fee_actual IS NOT NULL THEN o.id END) as orders_with_known_fees')]);
        $collectedTotal = (int) $paymentBreakdown->sum(fn ($row) => FinanceAnalyticsService::cents($row->collected_amount));
        $expenseBreakdown = DB::table('expenses')->whereBetween('expense_date', [$from, $to])
            ->select('expense_type', DB::raw('COUNT(*) as entry_count'), DB::raw('SUM(amount) as total_amount'))
            ->groupBy('expense_type')->orderByDesc('total_amount')->get();
        $bestSellers = DB::table('order_items as oi')->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->leftJoin('products as p', 'p.id', '=', 'oi.product_id')
            ->where('o.created_at', '>=', $queryFrom)->where('o.created_at', '<', $queryTo)
            ->whereRaw($this->activeOrderPredicate('o'))
            ->select('oi.product_id', DB::raw('COALESCE(p.name, MAX(oi.product_name)) as product_name'), DB::raw('SUM(oi.quantity) as quantity'), DB::raw('SUM(oi.subtotal) as sales_total'))
            ->groupBy('oi.product_id', 'p.name')->orderByDesc('quantity')->orderBy('oi.product_id')->limit(10)->get();
        $completedBestSellers = DB::table('order_items as oi')->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->leftJoin('products as p', 'p.id', '=', 'oi.product_id')
            ->where('o.created_at', '>=', $queryFrom)->where('o.created_at', '<', $queryTo)
            ->whereRaw($this->activeOrderPredicate('o'))
            ->whereRaw($this->completedOrderPredicate('o'))
            ->select('oi.product_id', DB::raw('COALESCE(p.name, MAX(oi.product_name)) as product_name'), DB::raw('SUM(oi.quantity) as quantity'), DB::raw('SUM(oi.subtotal) as sales_total'))
            ->groupBy('oi.product_id', 'p.name')->orderByDesc('quantity')->orderBy('oi.product_id')->limit(10)->get();
        $breakdownCharts = [
            'payments' => [
                'unit' => 'money',
                'labels' => $paymentBreakdown->map(fn ($row) => strtoupper($row->payment_method))->all(),
                'series' => [
                    ['label' => 'Đã thu', 'color' => '#be185d', 'values' => $paymentBreakdown->map(fn ($row) => FinanceAnalyticsService::cents($row->collected_amount))->all()],
                    ['label' => 'Chờ thu', 'color' => '#d97706', 'values' => $paymentBreakdown->map(fn ($row) => FinanceAnalyticsService::cents($row->awaiting_amount))->all()],
                ],
            ],
            'expenses' => [
                'unit' => 'money',
                'showShare' => true,
                'labels' => $expenseBreakdown->map(fn ($row) => ucfirst(str_replace('_', ' ', $row->expense_type)))->all(),
                'series' => [['label' => 'Chi phí đã ghi', 'color' => '#047857', 'values' => $expenseBreakdown->map(fn ($row) => FinanceAnalyticsService::cents($row->total_amount))->all()]],
            ],
            'products' => [
                'unit' => 'count',
                'labels' => $completedBestSellers->map(fn ($row) => $row->product_name)->all(),
                'series' => [['label' => 'Đã hoàn tất', 'color' => '#7c3aed', 'values' => $completedBestSellers->map(fn ($row) => (int) $row->quantity)->all()]],
            ],
        ];

        $monthLinks = collect($chartDashboard['monthly'])->map(fn ($row) => route('admin.finance.revenue', ['period' => 'month', 'period_date' => $row['from']]))->all();
        $quarterLinks = collect($chartDashboard['quarterly'])->map(fn ($row) => route('admin.finance.revenue', ['period' => 'quarter', 'period_date' => $row['from']]))->all();
        $yearLinks = collect($chartDashboard['yearly'])->map(fn ($row) => route('admin.finance.revenue', ['period' => 'year', 'period_date' => $row['from']]))->all();
        $monthlyPoints = collect($chartDashboard['monthly']);
        $quarters = collect($chartDashboard['quarterly']);
        $years = collect($chartDashboard['yearly']);
        $monthComparison = $chartDashboard['month_comparison'];
        $quarterComparison = $chartDashboard['quarter_comparison'];
        $yearComparison = $chartDashboard['year_comparison'];
        $growthLabels = ['MoM', 'QoQ', 'YoY', 'Kỳ đã chọn'];
        $selectedGrowth = $periodComparison;
        $rangeDays = (int) $rangeStart->diffInDays($rangeEnd) + 1;
        if ($firstCompletedOrder && $rangeDays <= 62) {
            $timeSeries = [];
            for ($day = $trendFrom->startOfDay(); $day->lessThanOrEqualTo($rangeEnd->startOfDay()); $day = $day->addDay()) {
                $date = $day->toDateString();
                $daily = $actual['daily'][$date] ?? null;
                $timeSeries[] = [
                    'label' => $day->format('d/m'),
                    'details' => $day->format('d/m/Y'),
                    'from' => $date,
                    'to' => $date,
                    'revenue' => $daily ? $daily['net_product_revenue'] + $daily['shipping_income'] : 0,
                ];
            }
        } elseif ($firstCompletedOrder) {
            $timeSeries = $rangeDays <= 180 ? $comparison['weekly_trend'] : $comparison['monthly_trend'];
            $timeSeries = array_map(fn ($row) => [
                ...$row,
                'details' => $row['from'].' – '.$row['to'],
            ], $timeSeries);
        } else {
            $timeSeries = [];
        }
        $analyticsCharts = [
            'revenue_12m' => ['unit' => 'money', 'labels' => $monthlyPoints->pluck('label')->all(), 'links' => $monthLinks,
                'details' => $monthlyPoints->map(fn ($row) => ($row['order_count'] ?? 0).' đơn'.($row['partial'] ? ' · kỳ đang diễn ra' : ''))->all(),
                'series' => [['label' => 'Doanh thu đã ghi nhận', 'color' => '#be185d', 'values' => $monthlyPoints->pluck('revenue')->all()]]],
            'month_comparison' => ['unit' => 'money', 'labels' => [$monthComparison['current_label'], $monthComparison['previous_label']],
                'links' => [route('admin.finance.revenue', ['period' => 'month', 'period_date' => CarbonImmutable::create($chartYear, $anchor->month, 1)->toDateString()]), route('admin.finance.revenue', ['period' => 'month', 'period_date' => CarbonImmutable::create($chartYear, $anchor->month, 1)->subMonthNoOverflow()->toDateString()])],
                'details' => ['Kỳ hiện tại', 'Kỳ đối chiếu'], 'series' => [['label' => 'Doanh thu', 'color' => '#be185d', 'values' => [$monthComparison['revenue']['current'], $monthComparison['revenue']['previous']]]]],
            'quarterly' => ['unit' => 'money', 'labels' => $quarters->pluck('label')->all(), 'links' => $quarterLinks,
                'details' => $quarters->map(fn ($row) => ($row['order_count'] ?? 0).' đơn · Giá vốn: '.($row['cogs'] === null ? 'thiếu dữ liệu' : number_format($row['cogs'] / 100, 0, ',', '.').' đ').' · Chi phí: '.($row['total_costs'] === null ? 'thiếu dữ liệu' : number_format($row['total_costs'] / 100, 0, ',', '.').' đ'))->all(),
                'series' => [['label' => 'Doanh thu', 'color' => '#be185d', 'values' => $quarters->pluck('revenue')->all()], ['label' => 'Lợi nhuận trước thuế', 'color' => '#047857', 'values' => $quarters->pluck('profit')->all()]]],
            'yearly' => ['unit' => 'money', 'labels' => $years->pluck('label')->all(), 'links' => $yearLinks,
                'details' => $years->map(fn ($row) => ($row['partial'] ? 'Năm đang diễn ra' : 'Năm đủ kỳ').' · '.($row['order_count'] ?? 0).' đơn')->all(),
                'series' => [['label' => 'Doanh thu', 'color' => '#be185d', 'values' => $years->pluck('revenue')->all()], ['label' => 'Tổng chi phí đã biết', 'color' => '#d97706', 'values' => $years->pluck('total_costs')->all()], ['label' => 'Lợi nhuận trước thuế', 'color' => '#047857', 'values' => $years->pluck('profit')->all()]]],
            'revenue_profit' => ['unit' => 'money', 'labels' => $monthlyPoints->pluck('label')->all(), 'links' => $monthLinks,
                'details' => $monthlyPoints->map(fn ($row) => 'Biên lợi nhuận trước thuế: '.($row['margin'] === null ? 'chưa đủ dữ liệu' : number_format($row['margin'], 2, ',', '.').'%'))->all(),
                'series' => [['label' => 'Doanh thu', 'color' => '#be185d', 'values' => $monthlyPoints->pluck('revenue')->all()], ['label' => 'Lợi nhuận trước thuế', 'color' => '#047857', 'values' => $monthlyPoints->pluck('profit')->all()]]],
            'monthly_costs' => ['unit' => 'money', 'labels' => $monthlyPoints->pluck('label')->all(), 'links' => $monthLinks, 'showShare' => true,
                'details' => $monthlyPoints->map(fn ($row) => $row['partial'] ? 'Kỳ đang diễn ra' : 'Chi phí từ dữ liệu đã ghi')->all(),
                'series' => [
                    ['label' => 'Giá vốn', 'color' => '#7c3aed', 'values' => $monthlyPoints->map(fn ($row) => $row['cost_components']['cogs'] ?? null)->all()],
                    ['label' => 'Vận chuyển', 'color' => '#d97706', 'values' => $monthlyPoints->map(fn ($row) => $row['cost_components']['shipping_cost_actual'] ?? null)->all()],
                    ['label' => 'Đóng gói', 'color' => '#0891b2', 'values' => $monthlyPoints->map(fn ($row) => $row['cost_components']['packaging_cost_actual'] ?? null)->all()],
                    ['label' => 'Phí thanh toán', 'color' => '#9333ea', 'values' => $monthlyPoints->map(fn ($row) => $row['cost_components']['payment_gateway_fee_actual'] ?? null)->all()],
                    ['label' => 'Trực tiếp khác', 'color' => '#b45309', 'values' => $monthlyPoints->map(fn ($row) => $row['cost_components']['other_direct_cost'] ?? null)->all()],
                    ['label' => 'Vận hành', 'color' => '#047857', 'values' => $monthlyPoints->map(fn ($row) => $row['cost_components']['operating_expenses'] ?? null)->all()],
                ]],
            'profit_margin' => ['unit' => 'percent', 'labels' => $monthlyPoints->pluck('label')->all(), 'links' => $monthLinks,
                'details' => $monthlyPoints->map(fn ($row) => $row['profit'] === null ? 'Lợi nhuận chưa xác định do thiếu chi phí' : 'Lợi nhuận trước thuế: '.number_format($row['profit'] / 100, 0, ',', '.').' đ')->all(),
                'series' => [['label' => 'Biên lợi nhuận trước thuế', 'color' => '#047857', 'values' => $monthlyPoints->pluck('margin')->all()]]],
            'best_sellers' => ['unit' => 'count', 'labels' => $completedBestSellers->map(fn ($row) => $row->product_name)->all(),
                'details' => $completedBestSellers->map(fn ($row) => number_format((float) $row->sales_total, 0, ',', '.').' đ doanh số hàng')->all(),
                'series' => [['label' => 'Số lượng đã hoàn tất', 'color' => '#7c3aed', 'values' => $completedBestSellers->map(fn ($row) => (int) $row->quantity)->all()]]],
            'payments' => $breakdownCharts['payments'],
            'cost_coverage' => ['unit' => 'money', 'showShare' => true,
                'labels' => ['Giá vốn', 'Vận chuyển', 'Đóng gói', 'Phí thanh toán', 'Trực tiếp khác', 'Vận hành'],
                'details' => ['Ảnh chụp giá vốn đơn hàng', 'Có thể thiếu chứng từ', 'Có thể thiếu chứng từ', 'Phí gateway đã ghi', 'Khoản trực tiếp đã ghi', 'Theo ngày chi thực tế'],
                'series' => [['label' => 'Chi phí đã ghi nhận', 'color' => '#047857', 'values' => array_map(fn ($key) => $actual['totals'][$key] === null ? null : $actual['totals'][$key], ['cogs', ...FinanceAnalyticsService::COST_FIELDS, 'operating_expenses'])]]],
            'daily_revenue' => ['unit' => 'money', 'labels' => array_column($timeSeries, 'label'),
                'links' => array_map(fn ($row) => route('admin.finance.revenue', ['period' => 'custom', 'from_date' => $row['from'], 'to_date' => $row['to']]), $timeSeries),
                'details' => array_column($timeSeries, 'details'),
                'series' => [['label' => 'Doanh thu thuần đơn hoàn thành', 'color' => '#be185d', 'values' => array_column($timeSeries, 'revenue')]]],
            'growth_comparisons' => ['unit' => 'money', 'labels' => $growthLabels,
                'details' => ['Tháng đã chọn so với tháng liền trước', 'Quý đã chọn so với quý liền trước', 'Năm đã chọn so với năm liền trước', $selectedGrowth['current_label'].' so với '.$selectedGrowth['previous_label']],
                'series' => [['label' => 'Chênh lệch doanh thu', 'color' => '#be185d', 'values' => [$monthComparison['revenue']['difference'], $quarterComparison['revenue']['difference'], $yearComparison['revenue']['difference'], $selectedGrowth['revenue']['difference']]], ['label' => 'Chênh lệch lợi nhuận trước thuế', 'color' => '#047857', 'values' => [$monthComparison['profit']['difference'], $quarterComparison['profit']['difference'], $yearComparison['profit']['difference'], $selectedGrowth['profit']['difference']]]]],
        ];
        $categoryBreakdown = DB::table('order_items as oi')->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->leftJoin('products as p', 'p.id', '=', 'oi.product_id')->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
            ->where('o.created_at', '>=', $queryFrom)->where('o.created_at', '<', $queryTo)
            ->whereRaw($this->activeOrderPredicate('o'))
            ->whereRaw($this->completedOrderPredicate('o'))
            ->groupBy('c.id', 'c.name')->orderByDesc(DB::raw('SUM(oi.subtotal)'))
            ->get([DB::raw("COALESCE(c.name, 'Không phân loại') as category_name"), DB::raw('COUNT(DISTINCT o.id) as order_count'), DB::raw('SUM(oi.quantity) as quantity'), DB::raw('SUM(oi.subtotal) as sales_total')]);
        $ordersByStatus = DB::table('orders')->where('created_at', '>=', $queryFrom)->where('created_at', '<', $queryTo)
            ->select('order_status', DB::raw('COUNT(*) as order_count'))->groupBy('order_status')->orderByDesc('order_count')->get();
        $averageOrderValue = ($actual['totals']['orders_count'] ?? 0) > 0 ? (int) round($actual['totals']['orders_value'] / $actual['totals']['orders_count']) : 0;
        $analyticsCharts['category_revenue'] = ['unit' => 'money', 'labels' => $categoryBreakdown->pluck('category_name')->all(), 'showShare' => true,
            'details' => $categoryBreakdown->map(fn ($row) => $row->quantity.' sản phẩm · '.$row->order_count.' đơn, doanh thu hàng trước phân bổ giảm giá')->all(),
            'series' => [['label' => 'Doanh thu hàng trước phân bổ giảm giá', 'color' => '#be185d', 'values' => $categoryBreakdown->map(fn ($row) => FinanceAnalyticsService::cents($row->sales_total))->all()]]];
        $analyticsCharts['period_comparison'] = ['unit' => 'money', 'labels' => ['Doanh thu kỳ chọn', 'Lợi nhuận kỳ chọn'],
            'details' => [$selectedGrowth['current_label'].' so với '.$selectedGrowth['previous_label'], 'Lợi nhuận trước thuế; thiếu dữ liệu được giữ trống'],
            'series' => [['label' => 'Kỳ hiện tại', 'color' => '#be185d', 'values' => [$selectedGrowth['revenue']['current'], $selectedGrowth['profit']['current']]], ['label' => 'Kỳ đối chiếu', 'color' => '#9ca3af', 'values' => [$selectedGrowth['revenue']['previous'], $selectedGrowth['profit']['previous']]]]];

        return view('admin.finance.'.$page, compact('actual', 'comparison', 'dataCoverage', 'forecast', 'from', 'to', 'period', 'chartYear', 'chartDashboard', 'analyticsCharts', 'categoryBreakdown', 'ordersByStatus', 'averageOrderValue', 'taxCompliance', 'periodComparison', 'paymentBreakdown', 'collectedTotal', 'expenseBreakdown', 'bestSellers', 'completedBestSellers', 'breakdownCharts'));
    }

    public function export(Request $request, FinanceAnalyticsService $finance)
    {
        $data = $request->validate([
            'from_date' => ['required', 'date_format:Y-m-d'],
            'to_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:from_date'],
        ]);
        $fromDate = $data['from_date'];
        $toDate = $data['to_date'];
        $displayTimezone = config('app.display_timezone', 'Asia/Ho_Chi_Minh');
        $from = CarbonImmutable::parse($fromDate, $displayTimezone)->startOfDay();
        $to = CarbonImmutable::parse($toDate, $displayTimezone)->startOfDay();
        $actual = $finance->getPeriodSummary($fromDate, $toDate);
        $forecast = $finance->getPeriodSummary($fromDate, $toDate, true);
        [$queryFrom, $queryTo] = FinanceDateRange::utcBounds($fromDate, $toDate);
        $orders = Order::with('items')->where('created_at', '>=', $queryFrom)->where('created_at', '<', $queryTo)->oldest()->get();
        $costs = DB::table('order_costs')->whereIn('order_id', $orders->modelKeys())->get()->keyBy('order_id');
        $orderRows = $orders->map(fn (Order $order) => [
            'order' => $order,
            'summary' => $finance->orderSummary($order, $costs->get($order->id)),
        ]);
        $expenses = DB::table('expenses')->whereBetween('expense_date', [$fromDate, $toDate])->orderBy('expense_date')->get();
        $taxSettings = DB::table('tax_settings')->where('effective_from', '<=', $toDate)
            ->where(fn ($query) => $query->whereNull('effective_to')->orWhere('effective_to', '>=', $fromDate))
            ->orderBy('effective_from')->get();
        $taxRecords = DB::table('tax_records')->where('period_start', '<=', $toDate)
            ->where('period_end', '>=', $fromDate)->orderBy('period_start')->get();
        $products = DB::table('products')->orderBy('id')->get();
        $summaryFields = [
            'order_count' => ['Số đơn hoàn thành', 'count'],
            'recognized_count' => ['Số đơn ghi nhận doanh thu', 'count'],
            'revenue' => ['Tổng doanh thu tự động ghi nhận', 'money'],
            'orders_value' => ['Tổng giá trị đơn phát sinh', 'money'],
            'awaiting_count' => ['Số đơn chờ ghi nhận', 'count'],
            'awaiting_revenue' => ['Giá trị chờ ghi nhận', 'money'],
            'completed_revenue' => ['Doanh thu đơn hoàn thành theo chi tiết', 'money'],
            'net_revenue' => ['Doanh thu thuần đơn hoàn thành', 'money'],
            'cogs' => ['Giá vốn hàng bán', 'money'],
            'gross_profit' => ['Lợi nhuận gộp', 'money'],
            'shipping_income' => ['Ship khách trả', 'money'],
            'gift_card_income' => ['Thu thiệp đã lưu', 'money'],
            'gift_wrap_income' => ['Thu gói quà đã lưu', 'money'],
            'gift_service_income' => ['Thu thiệp và gói quà đã lưu', 'money'],
            'unallocated_income' => ['Chênh lệch doanh thu chưa phân bổ', 'money'],
            'shipping_cost_actual' => ['Ship thực tế', 'money'],
            'packaging_cost_actual' => ['Chi phí đóng gói', 'money'],
            'payment_gateway_fee_actual' => ['Phí thanh toán', 'money'],
            'other_direct_cost' => ['Chi phí trực tiếp khác', 'money'],
            'operating_expenses' => ['Chi phí vận hành', 'money'],
            'cancelled_costs' => ['Chi phí đơn hủy đã ghi nhận', 'money'],
            'recorded_costs_subtotal' => ['Chi phí đã ghi theo nguồn', 'money'],
            'configured_costs_estimate' => ['Chi phí theo cấu hình, chỉ tạm tính', 'money'],
            'costs_shown_subtotal' => ['Tổng chi phí đã ghi và tạm tính', 'money'],
            'provisional_profit_before_tax' => ['Lợi nhuận trước thuế tạm tính, chưa gồm chi phí thiếu căn cứ', 'money'],
            'profit_before_tax' => ['Lợi nhuận trước thuế', 'money'],
            'estimated_tax' => ['Thuế dự tính', 'money'],
            'actual_tax' => ['Thuế thực tế', 'money'],
            'estimated_indirect_tax' => ['Thuế gián thu/VAT đầu ra dự tính', 'money'],
            'actual_indirect_tax' => ['Thuế gián thu/VAT thực tế', 'money'],
            'net_profit_after_tax' => ['Lợi nhuận sau thuế thực tế', 'money'],
            'estimated_net_profit_after_tax' => ['Lợi nhuận sau thuế dự tính', 'money'],
            'profit_margin' => ['Biên lợi nhuận (%)', 'number'],
            'missing_orders' => ['Số đơn thiếu dữ liệu', 'count'],
        ];

        $filename = 'du-lieu-tai-chinh-'.$from->format('d-m-Y').'-den-'.$to->format('d-m-Y').'.csv';

        return response()->streamDownload(function () use ($actual, $expenses, $forecast, $from, $orderRows, $products, $summaryFields, $taxRecords, $taxSettings, $to) {
            $stream = fopen('php://output', 'wb');
            if ($stream === false) {
                return;
            }
            fwrite($stream, "\xEF\xBB\xBF");
            $write = static fn (array $row) => fputcsv($stream, $row, ';');
            $blank = static fn () => fwrite($stream, PHP_EOL);
            $vnd = static fn ($cents) => $cents === null ? 'Thiếu dữ liệu' : number_format($cents / 100, 2, '.', '');
            $rawMoney = static fn ($amount) => $amount === null ? 'Thiếu dữ liệu' : number_format((float) $amount, 2, '.', '');
            $date = static fn ($value) => $value ? CarbonImmutable::parse($value)->format('d/m/Y') : '';

            $write(['DỮ LIỆU TÀI CHÍNH BLOOMGIFT']);
            $write(['Kỳ dữ liệu', $from->format('d/m/Y').' - '.$to->format('d/m/Y')]);
            $blank();
            foreach ([['TỔNG HỢP THỰC HIỆN', $actual['totals']], ['TỔNG HỢP DỰ KIẾN', $forecast['totals']]] as [$title, $totals]) {
                $write([$title]);
                $write(['Chỉ số', 'Giá trị']);
                foreach ($summaryFields as $key => [$label, $type]) {
                    if (! array_key_exists($key, $totals)) {
                        continue;
                    }
                    $value = $totals[$key] ?? null;
                    $write([$label, $type === 'money' ? $vnd($value) : ($value ?? 'Thiếu dữ liệu')]);
                }
                $blank();
            }

            $write(['DỮ LIỆU ĐƠN HÀNG']);
            $write(['Mã đơn', 'Ngày tạo', 'Trạng thái', 'Doanh thu sản phẩm', 'Giảm giá', 'Doanh thu thuần sản phẩm', 'Ship khách trả', 'Thu thiệp', 'Thu gói quà', 'Chênh lệch chưa phân bổ', 'Giá vốn', 'Ship thực tế', 'Đóng gói', 'Phí thanh toán', 'Chi phí trực tiếp khác', 'Lợi nhuận trực tiếp', 'Tình trạng dữ liệu']);
            foreach ($orderRows as $row) {
                $order = $row['order'];
                $summary = $row['summary'];
                $write([
                    $order->order_code, $order->created_at_local->format('d/m/Y H:i'), $order->order_status,
                    $vnd($summary['product_revenue']), $vnd($summary['discount']), $vnd($summary['net_product_revenue']),
                    $vnd($summary['shipping_income']), $vnd($summary['gift_card_income']), $vnd($summary['gift_wrap_income']),
                    $vnd($summary['unallocated_income']), $vnd($summary['cogs']), $vnd($summary['shipping_cost_actual']),
                    $vnd($summary['packaging_cost_actual']), $vnd($summary['payment_gateway_fee_actual']),
                    $vnd($summary['other_direct_cost']), $vnd($summary['order_profit']),
                    $summary['data_status'],
                ]);
            }
            $blank();

            $write(['CHI PHÍ VẬN HÀNH']);
            $write(['Mã', 'Ngày chi', 'Loại', 'Nội dung', 'Số tiền', 'Ghi chú']);
            foreach ($expenses as $expense) {
                $write([$expense->expense_code, $date($expense->expense_date), $expense->expense_type, $expense->description, $rawMoney($expense->amount), $expense->note]);
            }
            $blank();

            $write(['CẤU HÌNH THUẾ']);
            $write(['Loại', 'Tên', 'Nhóm thuế', 'Cơ sở tính', 'Cách tính', 'Thuế suất (%)', 'Hiệu lực từ', 'Hiệu lực đến', 'Hoạt động']);
            foreach ($taxSettings as $setting) {
                $write([$setting->tax_type, $setting->tax_name, $setting->tax_category, $setting->tax_base, $setting->calculation_method, $setting->rate, $date($setting->effective_from), $date($setting->effective_to), $setting->is_active ? 'Có' : 'Không']);
            }
            $blank();

            $write(['HỒ SƠ THUẾ']);
            $write(['Từ ngày', 'Đến ngày', 'Loại', 'Nhóm thuế', 'Cách tính', 'Tiền tính thuế', 'Thuế suất (%)', 'Thuế dự tính', 'Thuế thực tế', 'Trạng thái']);
            foreach ($taxRecords as $record) {
                $write([$date($record->period_start), $date($record->period_end), $record->tax_type, $record->tax_category, $record->calculation_method, $rawMoney($record->taxable_amount), $record->tax_rate, $rawMoney($record->estimated_tax), $rawMoney($record->actual_tax), $record->status]);
            }
            $blank();

            $write(['GIÁ BÁN, GIÁ VỐN VÀ TỒN KHO SẢN PHẨM']);
            $write(['ID', 'Tên sản phẩm', 'Giá bán', 'Giá khuyến mãi', 'Giá vốn hiện tại', 'Tồn kho', 'Mùa vụ', 'Trạng thái bán', 'Trạng thái giá vốn']);
            foreach ($products as $product) {
                $season = ['spring' => 'Mùa xuân', 'summer' => 'Mùa hạ', 'autumn' => 'Mùa thu', 'winter' => 'Mùa đông', 'all' => 'Quanh năm'][$product->season] ?? $product->season;
                $write([$product->id, $product->name, $rawMoney($product->price), $rawMoney($product->sale_price), $rawMoney($product->cost_price), $product->stock, $season, $product->is_active ? 'Đang bán' : 'Tạm ẩn', $product->cost_price === null ? 'Chưa có dữ liệu' : 'Đã xác nhận']);
            }
            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function orders(Request $request)
    {
        $request->validate(['search' => ['nullable', 'string', 'max:100']]);
        $orders = Order::query()->when($request->filled('search'), fn ($q) => $q->where('order_code', 'like', '%'.$request->input('search').'%'))
            ->latest()->paginate(25)->withQueryString();

        return view('admin.finance.orders', compact('orders'));
    }

    public function order(Order $order, FinanceAnalyticsService $finance)
    {
        $order->load('items');
        $cost = DB::table('order_costs')->where('order_id', $order->id)->first();
        $summary = $finance->orderSummary($order, $cost);

        return view('admin.finance.order', compact('order', 'cost', 'summary'));
    }

    public function saveOrder(Request $request, Order $order, FinanceAnalyticsService $finance)
    {
        $rules = ['note' => ['nullable', 'string', 'max:5000'], 'snapshots' => ['nullable', 'array'], 'snapshots.*' => $this->money()];
        foreach (FinanceAnalyticsService::COST_FIELDS as $field) {
            $rules[$field] = $this->money();
        }
        $data = $request->validate($rules);
        DB::transaction(function () use ($order, $data) {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            foreach ($data['snapshots'] ?? [] as $id => $amount) {
                $item = $lockedOrder->items()->whereKey($id)->lockForUpdate()->first();
                if (! $item) {
                    throw ValidationException::withMessages(['snapshots' => 'Chi tiết đơn hàng không hợp lệ.']);
                }
                if ($amount !== null && $item->cost_price_snapshot === null) {
                    $item->timestamps = false;
                    $item->update(['cost_price_snapshot' => $amount, 'cost_price_snapshot_source' => 'manual']);
                }
            }
            $values = ['note' => $data['note'] ?? null, 'source' => 'manual', 'updated_at' => now()];
            foreach (FinanceAnalyticsService::COST_FIELDS as $field) {
                if (array_key_exists($field, $data)) {
                    $values[$field] = $data[$field];
                }
            }
            if (! DB::table('order_costs')->where('order_id', $order->id)->exists()) {
                $values['created_at'] = now();
            }
            DB::table('order_costs')->updateOrInsert(['order_id' => $order->id], $values);
        });
        $finance->finalizeOrderFinancials($order->fresh());

        return back()->with('success', 'Đã lưu khoản chi thủ công. Cần lưu/đối chiếu chứng từ trước khi xem là đã xác minh.');
    }

    public function settings(FinanceAnalyticsService $finance)
    {
        return view('admin.finance.settings', ['settings' => $finance->settings()]);
    }

    public function saveSettings(Request $request)
    {
        $data = $request->validate([
            'default_shipping_cost' => $this->money(true),
            'default_packaging_cost' => $this->money(true),
            'cod_fee' => $this->money(true),
            'paypal_fee_rate' => ['required', 'numeric', 'between:0,100', 'decimal:0,4'],
            'paypal_fixed_fee' => $this->money(true),
            'currency' => ['required', Rule::in(['VND', 'USD'])],
        ]);
        $data['updated_at'] = now();
        if (! DB::table('finance_settings')->where('store_key', 'default')->exists()) {
            $data['created_at'] = now();
        }
        DB::table('finance_settings')->updateOrInsert(['store_key' => 'default'], $data);

        return back()->with('success', 'Đã lưu cấu hình tài chính.');
    }

    public function expenses(Request $request)
    {
        $data = $request->validate([
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from_date'],
            'expense_type' => ['nullable', Rule::in(FinanceAnalyticsService::EXPENSE_TYPES)],
            'edit' => ['nullable', 'integer', 'exists:expenses,id'],
        ]);
        $expenses = DB::table('expenses')
            ->when($data['from_date'] ?? null, fn ($q, $value) => $q->where('expense_date', '>=', $value))
            ->when($data['to_date'] ?? null, fn ($q, $value) => $q->where('expense_date', '<=', $value))
            ->when($data['expense_type'] ?? null, fn ($q, $value) => $q->where('expense_type', $value))
            ->orderByDesc('expense_date')->paginate(25)->withQueryString();
        $editing = isset($data['edit']) ? DB::table('expenses')->find($data['edit']) : null;

        return view('admin.finance.expenses', compact('expenses', 'editing'));
    }

    public function saveExpense(Request $request)
    {
        $data = $request->validate([
            'id' => ['nullable', 'integer', 'exists:expenses,id'],
            'expense_type' => ['required', Rule::in(FinanceAnalyticsService::EXPENSE_TYPES)],
            'description' => ['required', 'string', 'max:255'],
            'amount' => $this->money(true),
            'expense_date' => ['required', 'date_format:Y-m-d'],
            'note' => ['nullable', 'string', 'max:5000'],
        ]);
        $id = $data['id'] ?? null;
        unset($data['id']);
        $data['updated_at'] = now();
        if ($id) {
            DB::table('expenses')->where('id', $id)->update($data);
        } else {
            DB::table('expenses')->insert($data + ['expense_code' => 'CP-'.Str::uuid(), 'created_by' => $request->user()->id, 'created_at' => now()]);
        }

        return redirect()->route('admin.finance.expenses')->with('success', 'Đã lưu chi phí vận hành.');
    }

    public function taxes(FinanceAnalyticsService $finance, TaxComplianceService $taxCompliance)
    {
        $compliance = $taxCompliance->summary();
        $profile = $compliance['profile'];
        $today = CarbonImmutable::now(config('app.display_timezone', 'Asia/Ho_Chi_Minh'))->startOfDay();
        $from = $profile ? $profile->tax_year.'-01-01' : $today->startOfYear()->toDateString();
        $yearEnd = $profile ? CarbonImmutable::create((int) $profile->tax_year)->endOfYear() : $today->endOfYear();
        $to = $today->min($yearEnd)->toDateString();
        $compliance['financial'] = $finance->getPeriodSummary($from, $to)['totals'];
        $compliance['period_from'] = $from;
        $compliance['period_to'] = $to;

        return view('admin.finance.taxes', [
            'settings' => DB::table('tax_settings')->orderByDesc('effective_from')->get(),
            'records' => DB::table('tax_records')->orderByDesc('period_start')->paginate(25),
            'compliance' => $compliance,
            'products' => DB::table('products')->orderBy('name')->get(['id', 'name']),
            'productTaxProfiles' => DB::table('product_tax_profiles')
                ->join('products', 'products.id', '=', 'product_tax_profiles.product_id')
                ->select('product_tax_profiles.*', 'products.name as product_name')
                ->orderBy('products.name')->get(),
        ]);
    }

    public function vatProducts()
    {
        $rules = collect();
        if (Schema::hasTable('vat_policy_rules')) {
            $rules = DB::table('vat_policy_rules as rules')
                ->leftJoin('categories', 'categories.id', '=', 'rules.category_id')
                ->leftJoin('users', 'users.id', '=', 'rules.created_by')
                ->select('rules.*', 'categories.name as category_name', 'users.name as creator_name')
                ->orderByDesc('rules.effective_from')->orderByDesc('rules.id')->get();
            $productNames = DB::table('products')->pluck('name', 'id');
            $rules->each(function ($rule) use ($productNames): void {
                $ids = json_decode((string) ($rule->product_ids ?? '[]'), true);
                $rule->product_names = collect(is_array($ids) ? $ids : [])
                    ->map(fn ($id) => $productNames->get($id, 'Sản phẩm #'.$id))->implode(', ');
            });
        }
        $firstOrderDate = DB::table('orders')->min('created_at');
        $vatHistory = $firstOrderDate
            ? app(TaxComplianceService::class)->estimateCatalogOutputVat(
                CarbonImmutable::parse($firstOrderDate, 'UTC')->setTimezone(config('app.display_timezone', 'Asia/Ho_Chi_Minh'))->toDateString(),
                CarbonImmutable::now(config('app.display_timezone', 'Asia/Ho_Chi_Minh'))->toDateString(),
            )
            : ['tax' => null, 'taxable_base' => null, 'unclassified_base' => null, 'complete' => false];

        return view('admin.finance.vat-products', [
            'rulesAvailable' => Schema::hasTable('vat_policy_rules'),
            'categories' => DB::table('categories')->orderBy('name')->get(['id', 'name']),
            'products' => DB::table('products')->orderBy('name')->get(['id', 'name', 'category_id', 'price', 'sale_price', 'is_active']),
            'rules' => $rules,
            'vatHistory' => $vatHistory,
            'vatHistoryFrom' => $firstOrderDate ? CarbonImmutable::parse($firstOrderDate, 'UTC')->setTimezone(config('app.display_timezone', 'Asia/Ho_Chi_Minh'))->toDateString() : null,
            'vatHistoryOrderCount' => Order::query()->completedOrDelivered()->count(),
            'vatCategories' => TaxComplianceService::VAT_CATEGORIES,
            'today' => CarbonImmutable::now(config('app.display_timezone', 'Asia/Ho_Chi_Minh'))->toDateString(),
        ]);
    }

    public function saveVatPolicy(Request $request)
    {
        if (! Schema::hasTable('vat_policy_rules')) {
            abort(503, 'Cấu trúc lịch sử VAT chưa được cài đặt.');
        }

        $data = $request->validate([
            'scope' => ['required', Rule::in(['store', 'category', 'products'])],
            'category_id' => ['required_if:scope,category', 'nullable', 'integer', 'exists:categories,id'],
            'product_ids' => ['required_if:scope,products', 'nullable', 'array', 'min:1', 'max:200'],
            'product_ids.*' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'vat_category' => ['required', Rule::in(array_keys(TaxComplianceService::VAT_CATEGORIES))],
            'vat_rate' => ['nullable', 'required_if:vat_category,custom', 'numeric', 'between:0,100', 'decimal:0,4'],
            'price_includes_vat' => ['required', 'boolean'],
            'effective_from' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'effective_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:effective_from'],
            'note' => ['nullable', 'string', 'max:5000'],
        ]);

        if ($data['vat_category'] !== 'custom') {
            $data['vat_rate'] = TaxComplianceService::VAT_CATEGORIES[$data['vat_category']]['rate'];
        }
        $productIds = array_values(array_unique(array_map('intval', $data['product_ids'] ?? [])));
        sort($productIds);
        $targetEnd = $data['effective_to'] ?? '9999-12-31';

        $existingRules = DB::table('vat_policy_rules')->where('scope', $data['scope'])->get();
        foreach ($existingRules as $existing) {
            $sameTarget = match ($data['scope']) {
                'store' => true,
                'category' => (int) $existing->category_id === (int) $data['category_id'],
                'products' => count(array_intersect($productIds, json_decode((string) ($existing->product_ids ?? '[]'), true) ?: [])) > 0,
            };
            $overlaps = $existing->effective_from <= $targetEnd
                && ($existing->effective_to === null || $existing->effective_to >= $data['effective_from']);

            if ($sameTarget && $overlaps && $data['effective_from'] <= $existing->effective_from) {
                throw ValidationException::withMessages([
                    'effective_from' => 'Phạm vi này đã có quy tắc cùng thời điểm hoặc bắt đầu sau ngày bạn chọn. Hãy chọn ngày hiệu lực mới hơn để giữ nguyên lịch sử.',
                ]);
            }
        }

        DB::table('vat_policy_rules')->insert([
            'scope' => $data['scope'],
            'category_id' => $data['scope'] === 'category' ? $data['category_id'] : null,
            'category_name_snapshot' => $data['scope'] === 'category'
                ? DB::table('categories')->where('id', $data['category_id'])->value('name')
                : null,
            'product_ids' => $data['scope'] === 'products' ? json_encode($productIds, JSON_THROW_ON_ERROR) : null,
            'vat_category' => $data['vat_category'],
            'vat_rate' => $data['vat_rate'],
            'price_includes_vat' => (bool) $data['price_includes_vat'],
            'effective_from' => $data['effective_from'],
            'effective_to' => $data['effective_to'] ?? null,
            'note' => $data['note'] ?? null,
            'created_by' => $request->user()->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.finance.vat-products')->with('success', 'Đã thêm quy tắc VAT mới. Quy tắc cũ được giữ lại trong lịch sử; không sửa đơn hàng cũ.');
    }

    public function saveTaxSetting(Request $request)
    {
        $request->merge([
            'tax_category' => $request->input('tax_category', 'income'),
            'calculation_method' => $request->input('calculation_method', 'exclusive'),
        ]);
        $data = $request->validate([
            'tax_type' => ['required', 'string', 'max:80', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'tax_name' => ['required', 'string', 'max:255'],
            'tax_category' => ['required', Rule::in(['income', 'indirect'])],
            'tax_base' => ['required', Rule::in(['net_revenue', 'net_product_revenue', 'profit_before_tax'])],
            'calculation_method' => ['required', Rule::in(['exclusive', 'inclusive'])],
            'rate' => ['required', 'numeric', 'between:0,100', 'decimal:0,4'],
            'effective_from' => ['required', 'date_format:Y-m-d'],
            'effective_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:effective_from'],
            'description' => ['nullable', 'string', 'max:5000'],
            'legal_reference' => ['nullable', 'string', 'max:500'],
            'legal_reference_url' => ['nullable', 'url:http,https', 'max:1000'],
            'note' => ['nullable', 'string', 'max:5000'],
        ]);
        $overlap = DB::table('tax_settings')->where('tax_type', $data['tax_type'])->where('is_active', true)
            ->where('effective_from', '<=', $data['effective_to'] ?? '9999-12-31')
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $data['effective_from']))->exists();
        if ($overlap) {
            throw ValidationException::withMessages(['effective_from' => 'Loại thuế này đã có cấu hình trong thời gian giao nhau.']);
        }
        DB::table('tax_settings')->insert($data + ['is_active' => true, 'is_demo' => false, 'created_at' => now(), 'updated_at' => now()]);

        return back()->with('success', 'Đã thêm cấu hình thuế.');
    }

    public function closeTaxSetting(Request $request, int $setting)
    {
        $row = DB::table('tax_settings')->find($setting);
        abort_unless($row, 404);
        $data = $request->validate(['effective_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$row->effective_from]]);
        $overlap = DB::table('tax_settings')->where('tax_type', $row->tax_type)->where('id', '!=', $setting)
            ->where('is_active', true)->where('effective_from', '<=', $data['effective_to'])
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $row->effective_from))->exists();
        if ($overlap) {
            throw ValidationException::withMessages(['effective_to' => 'Thời gian giao nhau với cấu hình khác.']);
        }
        DB::table('tax_settings')->where('id', $setting)->update($data + ['updated_at' => now()]);

        return back()->with('success', 'Đã cập nhật ngày kết thúc hiệu lực.');
    }

    public function saveTaxRecord(Request $request)
    {
        $request->merge([
            'tax_category' => $request->input('tax_category', 'income'),
            'calculation_method' => $request->input('calculation_method', 'exclusive'),
        ]);
        $data = $request->validate([
            'period_start' => ['required', 'date_format:Y-m-d'],
            'period_end' => ['required', 'date_format:Y-m-d', 'after_or_equal:period_start'],
            'tax_type' => ['required', 'string', 'max:80', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'tax_category' => ['required', Rule::in(['income', 'indirect'])],
            'taxable_amount' => $this->money(true),
            'tax_rate' => ['required', 'numeric', 'between:0,100', 'decimal:0,4'],
            'calculation_method' => ['required', Rule::in(['exclusive', 'inclusive'])],
            'actual_tax' => [...$this->money(), 'required_if:status,confirmed,paid'],
            'status' => ['required', Rule::in(['estimated', 'confirmed', 'paid', 'estimated_exempt', 'confirmed_exempt'])],
            'note' => ['nullable', 'string', 'max:5000'],
        ]);
        $key = array_intersect_key($data, array_flip(['period_start', 'period_end', 'tax_type']));
        $existing = DB::table('tax_records')->where($key)->first();
        $overlap = DB::table('tax_records')->where('tax_type', $data['tax_type'])
            ->where('period_start', '<=', $data['period_end'])->where('period_end', '>=', $data['period_start'])
            ->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))->exists();
        if ($overlap) {
            throw ValidationException::withMessages(['period_start' => 'Đã có hồ sơ cùng loại thuế trong kỳ giao nhau.']);
        }
        $amount = (float) $data['taxable_amount'];
        $rate = (float) $data['tax_rate'];
        $isExempt = in_array($data['status'], ['estimated_exempt', 'confirmed_exempt'], true);
        $data['estimated_tax'] = $isExempt ? 0 : ($data['calculation_method'] === 'inclusive'
            ? round($amount * $rate / (100 + $rate), 2)
            : round($amount * $rate / 100, 2));
        if ($data['status'] === 'confirmed_exempt') {
            $data['actual_tax'] = 0;
        }
        $data['updated_at'] = now();
        if (! $existing) {
            $data['created_at'] = now();
        }
        DB::table('tax_records')->updateOrInsert($key, $data);

        return back()->with('success', 'Đã lưu hồ sơ thuế theo kỳ.');
    }

    public function saveTaxEligibility(Request $request)
    {
        $data = $request->validate(['split_status' => ['required', Rule::in(['unknown', 'not_split', 'split'])]]);
        if (! Schema::hasColumn('tax_profiles', 'is_split_from_large_enterprise')) {
            abort(503, 'Cần cài migration cấu hình thuế trước khi lưu điều kiện này.');
        }
        $value = match ($data['split_status']) {
            'not_split' => false,
            'split' => true,
            default => null,
        };
        DB::table('tax_profiles')->where('store_key', 'default')->update(['is_split_from_large_enterprise' => $value, 'updated_at' => now()]);

        return back()->with('success', 'Đã cập nhật trạng thái điều kiện ưu đãi doanh nghiệp.');
    }

    public function saveProductTaxProfile(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'vat_category' => ['required', Rule::in(array_keys(TaxComplianceService::VAT_CATEGORIES))],
            'vat_rate' => ['nullable', 'required_if:vat_category,custom', 'numeric', 'between:0,100', 'decimal:0,4'],
            'price_includes_vat' => ['required', 'boolean'],
            'effective_from' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'effective_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:effective_from'],
            'note' => ['nullable', 'string', 'max:5000'],
        ]);
        if ($data['vat_category'] !== 'custom') {
            $data['vat_rate'] = TaxComplianceService::VAT_CATEGORIES[$data['vat_category']]['rate'];
        }
        if (DB::table('product_tax_profiles')->where('product_id', $data['product_id'])->exists()) {
            throw ValidationException::withMessages([
                'product_id' => 'Mapping VAT cũ được giữ nguyên để bảo toàn lịch sử. Hãy thêm quy tắc mới tại trang Cấu hình VAT sản phẩm.',
            ]);
        }
        $data['created_at'] = now();
        $data['updated_at'] = now();
        DB::table('product_tax_profiles')->insert($data);

        return back()->with('success', 'Đã lưu phân loại VAT của sản phẩm.');
    }
}
