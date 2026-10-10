<?php

namespace App\Services;

use App\Models\Order;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinanceAnalyticsService
{
    public const COST_FIELDS = ['shipping_cost_actual', 'packaging_cost_actual', 'payment_gateway_fee_actual', 'other_direct_cost'];

    public const EXPENSE_TYPES = ['advertising', 'salary', 'rent', 'electricity', 'water', 'internet', 'software', 'product_loss', 'office', 'maintenance', 'other'];

    // Work in minor units. Null remains unknown; an explicitly entered zero is known.
    public static function cents(mixed $amount): int
    {
        return (int) round((float) $amount * 100);
    }

    public function orderSummary(Order $order, ?object $cost = null): array
    {
        $items = $order->items;
        $revenue = $items->sum(fn ($item) => self::cents($item->price) * $item->quantity);
        $missingCogs = $items->isEmpty() || $items->contains(fn ($item) => $item->cost_price_snapshot === null);
        $cogs = $missingCogs ? null : $items->sum(fn ($item) => self::cents($item->cost_price_snapshot) * $item->quantity);
        $result = [
            'product_revenue' => $revenue,
            'discount' => self::cents($order->discount),
            'net_product_revenue' => $revenue - self::cents($order->discount),
            'shipping_income' => self::cents($order->shipping_fee),
            'cogs' => $cogs,
        ];
        foreach (self::COST_FIELDS as $field) {
            $result[$field] = ($cost?->$field) === null ? null : self::cents($cost->$field);
        }
        $missingDirectCosts = in_array(null, array_intersect_key($result, array_flip(self::COST_FIELDS)), true);
        $result['gross_profit'] = $cogs === null ? null : $result['net_product_revenue'] - $cogs;
        $result['financial_data_complete'] = ! $missingCogs && ! $missingDirectCosts;
        $result['order_profit'] = $result['financial_data_complete']
            ? $result['gross_profit'] + $result['shipping_income'] - array_sum(array_intersect_key($result, array_flip(self::COST_FIELDS)))
            : null;
        $hasBackfill = $items->contains(fn ($item) => str_starts_with((string) $item->cost_price_snapshot_source, 'backfill_'));
        $result['data_status'] = match (true) {
            $missingCogs => 'Thiếu giá vốn',
            $missingDirectCosts => 'Thiếu chi phí',
            ($cost->source ?? null) === 'manual' => 'Nhập thủ công',
            $hasBackfill => 'Dữ liệu bổ sung',
            default => 'Đủ dữ liệu tài chính',
        };
        // The legacy checkout includes card/wrap income in total but does not store its breakdown.
        // Expose it for reconciliation without inventing a breakdown or changing the agreed formula.
        $result['unallocated_income'] = self::cents($order->total) - $result['net_product_revenue'] - $result['shipping_income'];

        return $result;
    }

    public function calculateDirectOrderProfit(Order $order, ?object $cost = null): ?float
    {
        $profit = $this->orderSummary($order, $cost)['order_profit'];

        return $profit === null ? null : $profit / 100;
    }

    public function revenueFlow(?string $from = null, ?string $to = null): array
    {
        $orders = Order::query()
            ->when($from && $to, fn ($query) => $query->whereBetween('created_at', [
                CarbonImmutable::parse($from)->startOfDay(),
                CarbonImmutable::parse($to)->endOfDay(),
            ]))
            ->get(['id', 'status', 'order_status', 'payment_status', 'total_amount', 'total']);
        $activeOrders = $orders->reject(function (Order $order) {
            return in_array(strtolower((string) $order->status), ['cancelled', 'canceled'], true)
                || in_array(strtolower((string) $order->order_status), ['cancelled', 'canceled'], true);
        });
        $recognizedOrders = $activeOrders->filter(function (Order $order) {
            return strtolower((string) $order->payment_status) === 'paid'
                || in_array(strtolower((string) $order->status), ['completed', 'delivered', 'confirmed'], true)
                || in_array(strtolower((string) $order->order_status), ['completed', 'delivered', 'confirmed'], true);
        });
        $awaitingOrders = $activeOrders->whereNotIn('id', $recognizedOrders->pluck('id')->all());
        $value = fn ($rows) => $rows->sum(fn (Order $order) => self::cents($order->total_amount ?? $order->total));

        return [
            'orders_value' => $value($activeOrders),
            'orders_count' => $activeOrders->count(),
            'recognized_revenue' => $value($recognizedOrders),
            'recognized_count' => $recognizedOrders->count(),
            'awaiting_revenue' => $value($awaitingOrders),
            'awaiting_count' => $awaitingOrders->count(),
        ];
    }

    public function settings(): object
    {
        return DB::table('finance_settings')->where('store_key', 'default')->first() ?? (object) [
            'default_shipping_cost' => 0,
            'default_packaging_cost' => 0,
            'cod_fee' => 0,
            'paypal_fee_rate' => 0,
            'paypal_fixed_fee' => 0,
            'currency' => 'VND',
        ];
    }

    public function finalizeOrderFinancials(Order $order): bool
    {
        $status = strtolower((string) ($order->order_status ?: $order->status));
        if ($status !== 'completed') {
            return false;
        }

        return DB::transaction(function () use ($order): bool {
            $lockedOrder = Order::with('items')->lockForUpdate()->findOrFail($order->id);
            $settings = $this->settings();
            $existing = DB::table('order_costs')->where('order_id', $lockedOrder->id)->lockForUpdate()->first();
            $orderAmount = (float) ($lockedOrder->total_amount ?? $lockedOrder->total ?? 0);
            $paymentFee = strtolower((string) $lockedOrder->payment_method) === 'paypal'
                ? round($orderAmount * (float) $settings->paypal_fee_rate / 100 + (float) $settings->paypal_fixed_fee, 2)
                : (float) $settings->cod_fee;
            $defaults = [
                'shipping_cost_actual' => (float) $settings->default_shipping_cost,
                'packaging_cost_actual' => (float) $settings->default_packaging_cost,
                'payment_gateway_fee_actual' => $paymentFee,
                'other_direct_cost' => 0,
            ];
            $values = ['updated_at' => now()];
            foreach ($defaults as $field => $default) {
                if (($existing?->$field) === null) {
                    $values[$field] = $default;
                }
            }
            if (! $existing) {
                $values['source'] = 'system_default';
                $values['created_at'] = now();
            }

            $missingSnapshot = $lockedOrder->items->isEmpty()
                || $lockedOrder->items->contains(fn ($item) => $item->cost_price_snapshot === null);
            $costValues = array_map(fn ($field) => $values[$field] ?? $existing?->$field, self::COST_FIELDS);
            $complete = ! $missingSnapshot && ! in_array(null, $costValues, true);
            if ($complete) {
                $values['financial_finalized_at'] = $existing?->financial_finalized_at ?? now();
            }

            DB::table('order_costs')->updateOrInsert(['order_id' => $lockedOrder->id], $values);

            if (! $complete) {
                Log::warning('Không thể hoàn tất tài chính vì thiếu dữ liệu.', [
                    'order_id' => $lockedOrder->id,
                    'missing_cost_snapshot' => $missingSnapshot,
                ]);
            }

            return $complete;
        });
    }

    public function getPeriodSummary(string $from, string $to, bool $forecast = false, bool $withTax = true): array
    {
        $start = CarbonImmutable::parse($from)->startOfDay();
        $end = CarbonImmutable::parse($to)->endOfDay();
        $orders = Order::with('items')->whereBetween('created_at', [$start, $end])
            ->whereIn('order_status', $forecast ? ['pending', 'confirmed', 'processing', 'shipping'] : ['completed'])->get();
        $costs = DB::table('order_costs')->whereIn('order_id', $orders->modelKeys())->get()->keyBy('order_id');
        $fields = ['product_revenue', 'discount', 'net_product_revenue', 'shipping_income', 'cogs', 'gross_profit', ...self::COST_FIELDS, 'order_profit', 'unallocated_income'];
        $totals = array_fill_keys($fields, 0);
        $daily = [];
        $rows = [];
        foreach ($orders as $order) {
            $row = $this->orderSummary($order, $costs->get($order->id));
            $rows[] = ['order' => $order, 'summary' => $row];
            $date = $order->created_at->format('Y-m-d');
            $daily[$date] ??= array_fill_keys($fields, 0);
            foreach ($fields as $field) {
                $totals[$field] = $totals[$field] === null || $row[$field] === null ? null : $totals[$field] + $row[$field];
                $daily[$date][$field] = $daily[$date][$field] === null || $row[$field] === null ? null : $daily[$date][$field] + $row[$field];
            }
        }
        $totals['missing_orders'] = count(array_filter($rows, fn ($row) => ! $row['summary']['financial_data_complete']));
        $totals['order_count'] = $orders->count();
        $totals['completed_revenue'] = $totals['product_revenue'] + $totals['shipping_income'];
        $totals['revenue'] = $totals['completed_revenue'];
        $totals['net_revenue'] = $totals['net_product_revenue'] + $totals['shipping_income'];
        $totals['operating_expenses'] = 0;
        $totals['cancelled_costs'] = 0;
        if (! $forecast) {
            $flow = $this->revenueFlow($from, $to);
            $totals['revenue'] = $flow['recognized_revenue'];
            $totals = array_merge($totals, $flow);
            foreach (DB::table('expenses')->whereBetween('expense_date', [$from, $to])->get() as $expense) {
                $totals['operating_expenses'] += self::cents($expense->amount);
            }
            // Only recorded cancellation costs; no sales or COGS are inferred for cancellations.
            $cancelled = DB::table('order_costs')->join('orders', 'orders.id', '=', 'order_costs.order_id')
                ->where('orders.order_status', 'cancelled')->whereBetween('orders.created_at', [$start, $end])
                ->select('order_costs.*')->get();
            foreach ($cancelled as $cost) {
                foreach (self::COST_FIELDS as $field) {
                    if ($cost->$field === null) {
                        $totals['cancelled_costs'] = null;
                        break 2;
                    }
                    $totals['cancelled_costs'] += self::cents($cost->$field);
                }
            }
        }
        $totals['profit_before_tax'] = $totals['order_profit'] === null || $totals['cancelled_costs'] === null ? null
            : $totals['order_profit'] - $totals['operating_expenses'] - $totals['cancelled_costs'];
        $tax = $forecast || ! $withTax ? [
            'estimated_tax' => null,
            'actual_tax' => null,
            'estimated_indirect_tax' => null,
            'actual_indirect_tax' => null,
            'estimated_indirect_tax_base' => null,
            'cit_status' => null,
            'cit_rate' => null,
        ] : $this->calculateTax($from, $to, $totals);
        $totals = array_merge($totals, $tax);
        $totals['net_profit_after_tax'] = $totals['profit_before_tax'] === null || $tax['actual_tax'] === null ? null : $totals['profit_before_tax'] - $tax['actual_tax'];
        $totals['estimated_net_profit_after_tax'] = $totals['profit_before_tax'] === null || $tax['estimated_tax'] === null ? null : $totals['profit_before_tax'] - $tax['estimated_tax'];
        $totals['profit_margin'] = $totals['net_profit_after_tax'] === null || $totals['net_revenue'] <= 0 ? null : round($totals['net_profit_after_tax'] / $totals['net_revenue'] * 100, 2);
        ksort($daily);

        return ['totals' => $totals, 'rows' => $rows, 'daily' => $daily];
    }

    /**
     * Read-only finance comparisons for the dashboard. Each period delegates to
     * getPeriodSummary so revenue, costs and profit cannot drift from the report.
     *
     * @return array{week: array<string, mixed>, month: array<string, mixed>, weekly_trend: array<int, array<string, mixed>>, monthly_trend: array<int, array<string, mixed>>}
     */
    public function comparisonDashboard(?CarbonImmutable $today = null, ?CarbonImmutable $trendStart = null, ?CarbonImmutable $trendEnd = null): array
    {
        $today ??= CarbonImmutable::today();
        $trendStart ??= $today->startOfYear();
        $trendEnd ??= $today;
        $weekStart = $today->startOfWeek();
        $monthStart = $today->startOfMonth();

        return [
            'week' => $this->comparison(
                $this->snapshot($weekStart, $today),
                $this->snapshot($weekStart->subWeek(), $weekStart->subDay()),
                'Tuần này',
                'Tuần trước',
            ),
            'month' => $this->comparison(
                $this->snapshot($monthStart, $today),
                $this->snapshot($monthStart->subMonthNoOverflow(), $monthStart->subDay()),
                'Tháng này',
                'Tháng trước',
            ),
            'weekly_trend' => $this->weeklyTrend($trendStart, $trendEnd),
            'monthly_trend' => $this->monthlyTrend($trendStart, $trendEnd),
        ];
    }

    /** @return array{revenue: int, profit: ?int, margin: ?float} */
    private function snapshot(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $totals = $this->getPeriodSummary($from->toDateString(), $to->toDateString(), false, false)['totals'];
        $revenue = $totals['revenue'];
        $profit = $totals['profit_before_tax'];

        return [
            'revenue' => $revenue,
            'profit' => $profit,
            'margin' => $profit === null || $revenue <= 0 ? null : round($profit / $revenue * 100, 2),
        ];
    }

    /** @return array<string, mixed> */
    private function comparison(array $current, array $previous, string $currentLabel, string $previousLabel): array
    {
        return [
            'current_label' => $currentLabel,
            'previous_label' => $previousLabel,
            'revenue' => $this->metricChange($current['revenue'], $previous['revenue']),
            'profit' => $this->metricChange($current['profit'], $previous['profit']),
        ];
    }

    /** @return array{current: ?int, previous: ?int, difference: ?int, percentage: ?float, state: string} */
    private function metricChange(?int $current, ?int $previous): array
    {
        if ($current === null || $previous === null) {
            return ['current' => $current, 'previous' => $previous, 'difference' => null, 'percentage' => null, 'state' => 'unavailable'];
        }

        $difference = $current - $previous;
        if ($previous === 0) {
            return [
                'current' => $current,
                'previous' => $previous,
                'difference' => $difference,
                'percentage' => $current === 0 ? 0 : null,
                'state' => $current === 0 ? 'steady' : 'new',
            ];
        }

        $percentage = round($difference / abs($previous) * 100, 2);

        return [
            'current' => $current,
            'previous' => $previous,
            'difference' => $difference,
            'percentage' => $percentage,
            'state' => $difference > 0 ? 'up' : ($difference < 0 ? 'down' : 'steady'),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function weeklyTrend(CarbonImmutable $periodStart, CarbonImmutable $periodEnd): array
    {
        $firstWeekStart = $periodStart->startOfWeek();
        $lastWeekStart = $periodEnd->startOfWeek();
        $weeks = $firstWeekStart->diffInWeeks($lastWeekStart);

        return collect(range(0, $weeks))->map(function (int $offset) use ($firstWeekStart, $periodStart, $periodEnd): array {
            $weekStart = $firstWeekStart->addWeeks($offset);
            $weekEnd = $weekStart->endOfWeek();
            $start = $weekStart->lessThan($periodStart) ? $periodStart : $weekStart;
            $end = $weekEnd->greaterThan($periodEnd) ? $periodEnd : $weekEnd;

            return [
                'label' => $start->format('d/m') . ' – ' . $end->format('d/m'),
                'from' => $start->toDateString(),
                'to' => $end->toDateString(),
                ...$this->snapshot($start, $end),
            ];
        })->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function monthlyTrend(CarbonImmutable $periodStart, CarbonImmutable $periodEnd): array
    {
        $firstMonthStart = $periodStart->startOfMonth();
        $lastMonthStart = $periodEnd->startOfMonth();
        $months = $firstMonthStart->diffInMonths($lastMonthStart);

        return collect(range(0, $months))->map(function (int $offset) use ($firstMonthStart, $periodStart, $periodEnd): array {
            $monthStart = $firstMonthStart->addMonthsNoOverflow($offset);
            $monthEnd = $monthStart->endOfMonth();
            $start = $monthStart->lessThan($periodStart) ? $periodStart : $monthStart;
            $end = $monthEnd->greaterThan($periodEnd) ? $periodEnd : $monthEnd;

            return [
                'label' => 'Tháng ' . $start->format('m/Y'),
                'from' => $start->toDateString(),
                'to' => $end->toDateString(),
                ...$this->snapshot($start, $end),
            ];
        })->all();
    }

    public function calculateTax(string $from, string $to, array $totals): array
    {
        return app(TaxComplianceService::class)->calculatePeriodTaxes($from, $to, $totals);
    }
}
