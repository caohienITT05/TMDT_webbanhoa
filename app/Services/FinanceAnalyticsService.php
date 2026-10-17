<?php

namespace App\Services;

use App\Models\Order;
use App\Support\FinanceDateRange;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class FinanceAnalyticsService
{
    public const COST_FIELDS = ['shipping_cost_actual', 'packaging_cost_actual', 'payment_gateway_fee_actual', 'other_direct_cost'];

    public const EXPENSE_TYPES = ['advertising', 'salary', 'rent', 'electricity', 'water', 'internet', 'software', 'product_loss', 'office', 'maintenance', 'other'];

    // Work in minor units. Null remains unknown; an explicitly entered zero is known.
    public static function cents(mixed $amount): int
    {
        return (int) round((float) $amount * 100);
    }

    private function cancelledOrderPredicate(string $alias): string
    {
        $conditions = [];
        foreach (['order_status', 'status', 'payment_status'] as $field) {
            if (Schema::hasColumn('orders', $field)) {
                $conditions[] = "LOWER(COALESCE({$alias}.{$field}, '')) IN ('cancelled','canceled')";
            }
        }

        return $conditions ? '('.implode(' OR ', $conditions).')' : '0 = 1';
    }

    public static function orderTotal(Order $order): mixed
    {
        $totalAmount = $order->total_amount;
        $legacyTotal = $order->total;

        return $totalAmount === null || ((float) $totalAmount === 0.0 && (float) $legacyTotal !== 0.0)
            ? $legacyTotal
            : $totalAmount;
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
        $giftCardIncome = $order->gift_card_fee === null ? null : self::cents($order->gift_card_fee);
        $giftWrapIncome = $order->gift_wrap_fee === null ? null : self::cents($order->gift_wrap_fee);
        $giftIncomeKnown = $giftCardIncome !== null && $giftWrapIncome !== null;
        $giftServiceIncome = $giftIncomeKnown ? $giftCardIncome + $giftWrapIncome : null;
        $orderTotal = self::cents(self::orderTotal($order));
        $unallocatedIncome = $orderTotal - $result['net_product_revenue'] - $result['shipping_income'] - ($giftServiceIncome ?? 0);
        $result['gift_card_income'] = $giftCardIncome;
        $result['gift_wrap_income'] = $giftWrapIncome;
        $result['gift_service_income'] = $giftServiceIncome;
        $result['unallocated_income'] = $unallocatedIncome;
        foreach (self::COST_FIELDS as $field) {
            $result[$field] = ($cost?->$field) === null ? null : self::cents($cost->$field);
        }
        $missingDirectCosts = in_array(null, array_intersect_key($result, array_flip(self::COST_FIELDS)), true);
        $costSource = $cost?->source;
        $result['gross_profit'] = $cogs === null ? null : $result['net_product_revenue'] - $cogs;
        $estimatedOrderProfit = ! $missingCogs && ! $missingDirectCosts && in_array($costSource, ['system_default', 'manual'], true)
            ? $result['gross_profit'] + $result['shipping_income'] + ($giftServiceIncome ?? 0) - array_sum(array_intersect_key($result, array_flip(self::COST_FIELDS)))
            : null;
        $result['cost_source'] = $costSource;
        $result['estimated_order_profit'] = $costSource === 'system_default' ? $estimatedOrderProfit : null;
        $result['financial_data_complete'] = ! $missingCogs && ! $missingDirectCosts && $costSource === 'manual' && $unallocatedIncome === 0;
        $result['order_profit'] = $result['financial_data_complete']
            ? $estimatedOrderProfit
            : null;
        $hasBackfill = $items->contains(fn ($item) => str_starts_with((string) $item->cost_price_snapshot_source, 'backfill_'));
        $result['data_status'] = match (true) {
            $missingCogs => 'Thiếu giá vốn',
            $missingDirectCosts => 'Thiếu chi phí',
            $costSource === 'system_default' => 'Ước tính theo cấu hình chi phí',
            $costSource === 'manual' => 'Chi phí nhập thủ công, chưa xác minh chứng từ',
            $costSource === null => 'Chưa rõ nguồn chi phí',
            $hasBackfill => 'Dữ liệu bổ sung',
            default => 'Chưa đối soát chi phí',
        };

        return $result;
    }

    public function calculateDirectOrderProfit(Order $order, ?object $cost = null): ?float
    {
        $profit = $this->orderSummary($order, $cost)['order_profit'];

        return $profit === null ? null : $profit / 100;
    }

    public function revenueFlow(?string $from = null, ?string $to = null): array
    {
        [$start, $endExclusive] = $from && $to ? FinanceDateRange::utcBounds($from, $to) : [null, null];
        $orderColumns = ['id', 'total'];
        foreach (['order_status', 'status', 'payment_status', 'total_amount'] as $optionalColumn) {
            if (Schema::hasColumn('orders', $optionalColumn)) {
                $orderColumns[] = $optionalColumn;
            }
        }
        $orders = Order::query()
            ->when($from && $to, fn ($query) => $query
                ->where('created_at', '>=', $start)
                ->where('created_at', '<', $endExclusive))
            ->get($orderColumns);
        $activeOrders = $orders->reject(function (Order $order) {
            $excluded = Order::SALES_INVALID_STATUSES;

            return in_array(strtolower((string) $order->status), $excluded, true)
                || in_array(strtolower((string) $order->order_status), $excluded, true)
                || in_array(strtolower((string) $order->payment_status), $excluded, true);
        });
        $recognizedOrders = $activeOrders->filter(function (Order $order) {
            $effectiveStatus = strtolower((string) ($order->order_status ?: $order->status));

            return strtolower((string) $order->payment_status) === 'paid'
                || in_array($effectiveStatus, ['completed', 'delivered', 'confirmed'], true);
        });
        $awaitingOrders = $activeOrders->whereNotIn('id', $recognizedOrders->pluck('id')->all());
        $value = fn ($rows) => $rows->sum(fn (Order $order) => self::cents(self::orderTotal($order)));

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
        if (! in_array($status, ['completed', 'delivered'], true)) {
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
            $mayApplyDefaults = ! $existing || $existing->source === 'system_default';
            foreach ($defaults as $field => $default) {
                if ($mayApplyDefaults && ($existing?->$field) === null) {
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
            $source = $existing?->source ?? ($values['source'] ?? null);
            $complete = ! $missingSnapshot && ! in_array(null, $costValues, true) && $source === 'manual';
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
        [$start, $endExclusive] = FinanceDateRange::utcBounds($from, $to);
        $orders = Order::with('items')->where('created_at', '>=', $start)->where('created_at', '<', $endExclusive)
            ->when($forecast, fn ($query) => $query->whereIn('order_status', ['pending', 'confirmed', 'processing', 'shipping']))
            ->when(! $forecast, fn ($query) => $query->completedOrDelivered())
            ->get();
        $costs = DB::table('order_costs')->whereIn('order_id', $orders->modelKeys())->get()->keyBy('order_id');
        $fields = ['product_revenue', 'discount', 'net_product_revenue', 'shipping_income', 'gift_card_income', 'gift_wrap_income', 'gift_service_income', 'cogs', 'gross_profit', ...self::COST_FIELDS, 'order_profit', 'estimated_order_profit', 'unallocated_income'];
        $totals = array_fill_keys($fields, 0);
        $estimatedOrderCount = 0;
        $daily = [];
        $rows = [];
        foreach ($orders as $order) {
            $row = $this->orderSummary($order, $costs->get($order->id));
            $rows[] = ['order' => $order, 'summary' => $row];
            $date = $order->created_at->copy()->setTimezone(config('app.display_timezone', 'Asia/Ho_Chi_Minh'))->format('Y-m-d');
            $daily[$date] ??= array_fill_keys($fields, 0);
            foreach ($fields as $field) {
                if ($field === 'estimated_order_profit') {
                    if ($row[$field] !== null) {
                        $totals[$field] += $row[$field];
                        $daily[$date][$field] += $row[$field];
                        $estimatedOrderCount++;
                    }

                    continue;
                }
                $totals[$field] = $totals[$field] === null || $row[$field] === null ? null : $totals[$field] + $row[$field];
                $daily[$date][$field] = $daily[$date][$field] === null || $row[$field] === null ? null : $daily[$date][$field] + $row[$field];
            }
        }
        if ($estimatedOrderCount === 0) {
            $totals['estimated_order_profit'] = null;
        }
        $totals['estimated_order_count'] = $estimatedOrderCount;
        $totals['missing_orders'] = count(array_filter($rows, fn ($row) => ! $row['summary']['financial_data_complete']));
        $totals['order_count'] = $orders->count();
        $totals['completed_revenue'] = $totals['product_revenue'] + $totals['shipping_income'];
        $totals['revenue'] = $totals['completed_revenue'];
        $totals['net_revenue'] = $totals['net_product_revenue'] + $totals['shipping_income'];
        $totals['operating_expenses'] = 0;
        $totals['cancelled_costs'] = 0;
        $expenseEntries = 0;
        if (! $forecast) {
            $flow = $this->revenueFlow($from, $to);
            $totals['revenue'] = $flow['recognized_revenue'];
            $totals = array_merge($totals, $flow);
            foreach (DB::table('expenses')->whereBetween('expense_date', [$from, $to])->get() as $expense) {
                $totals['operating_expenses'] += self::cents($expense->amount);
                $expenseEntries++;
            }
            if ($expenseEntries === 0) {
                $totals['operating_expenses'] = null;
            }
            // A canceled order without manually reconciled costs is unknown, not zero.
            $cancelled = DB::table('orders')
                ->leftJoin('order_costs', 'orders.id', '=', 'order_costs.order_id')
                ->whereRaw($this->cancelledOrderPredicate('orders'))
                ->where('orders.created_at', '>=', $start)->where('orders.created_at', '<', $endExclusive)
                ->get(['order_costs.source', ...array_map(fn ($field) => 'order_costs.'.$field, self::COST_FIELDS)]);
            foreach ($cancelled as $cost) {
                if ($cost->source !== 'manual') {
                    $totals['cancelled_costs'] = null;
                    break;
                }
                foreach (self::COST_FIELDS as $field) {
                    if ($cost->$field === null) {
                        $totals['cancelled_costs'] = null;
                        break 2;
                    }
                    $totals['cancelled_costs'] += self::cents($cost->$field);
                }
            }
        }
        $recordedCosts = 0;
        $estimatedCosts = 0;
        $recordedEntries = 0;
        $estimatedEntries = 0;
        $provisionalOrderProfit = 0;
        $provisionalProfitComplete = true;
        foreach ($rows as $row) {
            $summary = $row['summary'];
            if ($summary['cogs'] !== null) {
                $recordedCosts += $summary['cogs'];
                $recordedEntries++;
            }
            foreach (self::COST_FIELDS as $field) {
                if ($summary[$field] === null) {
                    continue;
                }
                if ($summary['cost_source'] === 'system_default') {
                    $estimatedCosts += $summary[$field];
                    $estimatedEntries++;
                } elseif ($summary['cost_source'] === 'manual') {
                    $recordedCosts += $summary[$field];
                    $recordedEntries++;
                }
            }
            $orderProfit = $summary['order_profit'] ?? $summary['estimated_order_profit'];
            if ($orderProfit === null) {
                $provisionalProfitComplete = false;
            } else {
                $provisionalOrderProfit += $orderProfit;
            }
        }
        if ($totals['operating_expenses'] !== null) {
            $recordedCosts += $totals['operating_expenses'];
            $recordedEntries++;
        }
        if ($totals['cancelled_costs'] !== null && $totals['cancelled_costs'] > 0) {
            $recordedCosts += $totals['cancelled_costs'];
            $recordedEntries++;
        }
        $totals['recorded_costs_subtotal'] = $recordedEntries ? $recordedCosts : null;
        $totals['configured_costs_estimate'] = $estimatedEntries ? $estimatedCosts : null;
        $totals['costs_shown_subtotal'] = $recordedEntries ? $recordedCosts + $estimatedCosts : null;
        $totals['costs_shown_incomplete'] = $totals['missing_orders'] > 0
            || $totals['operating_expenses'] === null || $totals['cancelled_costs'] === null;
        $totals['provisional_profit_before_tax'] = $provisionalProfitComplete && $totals['operating_expenses'] !== null
            ? $provisionalOrderProfit - $totals['operating_expenses'] - ($totals['cancelled_costs'] ?? 0)
            : null;
        $totals['profit_before_tax'] = $totals['order_profit'] === null || $totals['operating_expenses'] === null || $totals['cancelled_costs'] === null ? null
            : $totals['order_profit'] - $totals['operating_expenses'] - $totals['cancelled_costs'];
        $totals['estimated_direct_profit'] = $totals['estimated_order_profit'];
        $totals['estimated_profit_before_tax'] = $totals['estimated_order_profit'] === null || $totals['operating_expenses'] === null || $totals['cancelled_costs'] === null
            ? null
            : $totals['estimated_order_profit'] - $totals['operating_expenses'] - $totals['cancelled_costs'];
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
        $today ??= CarbonImmutable::now(config('app.display_timezone', 'Asia/Ho_Chi_Minh'))->startOfDay();
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
            'quarterly_trend' => $this->quarterlyTrend($trendStart, $trendEnd),
            'yearly_trend' => $this->yearlyTrend($trendStart, $trendEnd),
        ];
    }

    public function compareSelectedPeriod(string $from, string $to, string $period): array
    {
        $currentFrom = CarbonImmutable::parse($from, config('app.display_timezone', 'Asia/Ho_Chi_Minh'))->startOfDay();
        $currentTo = CarbonImmutable::parse($to, config('app.display_timezone', 'Asia/Ho_Chi_Minh'))->endOfDay();
        [$previousFrom, $previousTo] = match ($period) {
            'month' => [$currentFrom->subMonthNoOverflow(), $currentTo->subMonthNoOverflow()],
            'quarter' => [$currentFrom->subQuarterNoOverflow(), $currentTo->subQuarterNoOverflow()],
            'year' => [$currentFrom->subYearNoOverflow(), $currentTo->subYearNoOverflow()],
            'week' => [$currentFrom->subWeek(), $currentTo->subWeek()],
            default => (function () use ($currentFrom, $currentTo) {
                $days = max(1, $currentFrom->diffInDays($currentTo) + 1);

                return [$currentFrom->subDays($days), $currentFrom->subDay()];
            })(),
        };

        return $this->comparison(
            $this->snapshot($currentFrom, $currentTo),
            $this->snapshot($previousFrom, $previousTo),
            $currentFrom->format('d/m/Y').' – '.$currentTo->format('d/m/Y'),
            $previousFrom->format('d/m/Y').' – '.$previousTo->format('d/m/Y'),
        );
    }

    /** @return array{revenue: int, profit: ?int, margin: ?float} */
    private function snapshot(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $totals = $this->getPeriodSummary($from->toDateString(), $to->toDateString(), false, false)['totals'];
        $revenue = $totals['net_revenue'];
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
        if ($previous < 0) {
            return [
                'current' => $current,
                'previous' => $previous,
                'difference' => $difference,
                'percentage' => null,
                'state' => 'negative_base',
            ];
        }

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
                'label' => $start->format('d/m').' – '.$end->format('d/m'),
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
                'label' => 'Tháng '.$start->format('m/Y'),
                'from' => $start->toDateString(),
                'to' => $end->toDateString(),
                ...$this->snapshot($start, $end),
            ];
        })->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function quarterlyTrend(CarbonImmutable $periodStart, CarbonImmutable $periodEnd): array
    {
        $first = $periodStart->startOfQuarter();
        $last = $periodEnd->startOfQuarter();

        return collect(range(0, $first->diffInQuarters($last)))->map(function (int $offset) use ($first, $periodStart, $periodEnd): array {
            $quarterStart = $first->addQuarters($offset);
            $quarterEnd = $quarterStart->endOfQuarter();
            $start = $quarterStart->lessThan($periodStart) ? $periodStart : $quarterStart;
            $end = $quarterEnd->greaterThan($periodEnd) ? $periodEnd : $quarterEnd;

            return ['label' => 'Quý '.$quarterStart->quarter.'/'.$quarterStart->year, 'from' => $start->toDateString(), 'to' => $end->toDateString(), ...$this->snapshot($start, $end)];
        })->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function yearlyTrend(CarbonImmutable $periodStart, CarbonImmutable $periodEnd): array
    {
        $first = $periodStart->startOfYear();
        $last = $periodEnd->startOfYear();

        return collect(range(0, $first->diffInYears($last)))->map(function (int $offset) use ($first, $periodStart, $periodEnd): array {
            $yearStart = $first->addYears($offset);
            $yearEnd = $yearStart->endOfYear();
            $start = $yearStart->lessThan($periodStart) ? $periodStart : $yearStart;
            $end = $yearEnd->greaterThan($periodEnd) ? $periodEnd : $yearEnd;

            return ['label' => (string) $yearStart->year, 'from' => $start->toDateString(), 'to' => $end->toDateString(), ...$this->snapshot($start, $end)];
        })->all();
    }

    /** Build all chart datasets from the same order and expense summaries used by the report. */
    /** Build every monthly/quarterly/yearly chart from one read of source orders and expenses. */
    public function chartDashboard(int $selectedYear, CarbonImmutable $anchor, ?CarbonImmutable $today = null): array
    {
        $today ??= CarbonImmutable::now(config('app.display_timezone', 'Asia/Ho_Chi_Minh'))->startOfDay();
        $activityDates = array_filter([Order::query()->min('created_at'), DB::table('expenses')->min('expense_date')]);
        $firstActivityYear = $activityDates ? CarbonImmutable::parse(min($activityDates), 'UTC')->setTimezone(config('app.display_timezone', 'Asia/Ho_Chi_Minh'))->year : $selectedYear;
        $firstYear = min($selectedYear - 1, $firstActivityYear);
        $lastYear = max($selectedYear, $today->year);
        [$queryStart, $queryEnd] = FinanceDateRange::utcBounds($firstYear.'-01-01', $today->toDateString());

        $emptyBucket = static fn (): array => [
            'revenue' => 0,
            'order_count' => 0,
            'cogs' => 0,
            'cogs_known' => true,
            'direct_costs' => array_fill_keys(self::COST_FIELDS, 0),
            'direct_known' => array_fill_keys(self::COST_FIELDS, true),
            'order_profit' => 0,
            'profit_known' => true,
            'operating_expenses' => 0,
            'cancelled_costs' => 0,
            'cancelled_costs_known' => true,
        ];
        $monthlyBuckets = [];
        $ensureMonth = static function (string $month) use (&$monthlyBuckets, $emptyBucket): void {
            $monthlyBuckets[$month] ??= $emptyBucket();
        };

        if ($queryStart->lessThan($queryEnd)) {
            $orders = Order::query()
                ->with('items')
                ->where('created_at', '>=', $queryStart)
                ->where('created_at', '<', $queryEnd)
                ->completedOrDelivered()
                ->get();
            $costs = DB::table('order_costs')->whereIn('order_id', $orders->modelKeys())->get()->keyBy('order_id');

            foreach ($orders as $order) {
                $month = $order->created_at->copy()->setTimezone(config('app.display_timezone', 'Asia/Ho_Chi_Minh'))->format('Y-m');
                $ensureMonth($month);
                $summary = $this->orderSummary($order, $costs->get($order->id));
                $bucket = &$monthlyBuckets[$month];
                $bucket['revenue'] += self::cents(self::orderTotal($order));
                $bucket['order_count']++;
                if ($summary['cogs'] === null) {
                    $bucket['cogs_known'] = false;
                } else {
                    $bucket['cogs'] += $summary['cogs'];
                }
                foreach (self::COST_FIELDS as $field) {
                    if ($summary[$field] === null) {
                        $bucket['direct_known'][$field] = false;
                    } else {
                        $bucket['direct_costs'][$field] += $summary[$field];
                    }
                }
                if ($summary['order_profit'] === null) {
                    $bucket['profit_known'] = false;
                } else {
                    $bucket['order_profit'] += $summary['order_profit'];
                }
                unset($bucket);
            }

            foreach (DB::table('expenses')->where('expense_date', '>=', $queryStart->toDateString())->where('expense_date', '<=', $today->toDateString())->get(['expense_date', 'amount']) as $expense) {
                $month = CarbonImmutable::parse($expense->expense_date)->format('Y-m');
                $ensureMonth($month);
                $monthlyBuckets[$month]['operating_expenses'] += self::cents($expense->amount);
            }

            $cancelledCosts = DB::table('orders as o')->leftJoin('order_costs as oc', 'o.id', '=', 'oc.order_id')
                ->where('o.created_at', '>=', $queryStart)->where('o.created_at', '<', $queryEnd)
                ->whereRaw($this->cancelledOrderPredicate('o'))
                ->get(['o.created_at', 'oc.source', ...array_map(fn ($field) => 'oc.'.$field, self::COST_FIELDS)]);
            foreach ($cancelledCosts as $cost) {
                $month = CarbonImmutable::parse($cost->created_at, 'UTC')->setTimezone(config('app.display_timezone', 'Asia/Ho_Chi_Minh'))->format('Y-m');
                $ensureMonth($month);
                if ($cost->source !== 'manual') {
                    $monthlyBuckets[$month]['cancelled_costs_known'] = false;

                    continue;
                }
                foreach (self::COST_FIELDS as $field) {
                    if ($cost->$field === null) {
                        $monthlyBuckets[$month]['cancelled_costs_known'] = false;
                        break;
                    }
                    $monthlyBuckets[$month]['cancelled_costs'] += self::cents($cost->$field);
                }
            }
        }

        $mergeBuckets = static function (array $target, array $source): array {
            $target['revenue'] += $source['revenue'];
            $target['order_count'] += $source['order_count'];
            if (! $source['cogs_known']) {
                $target['cogs_known'] = false;
            } else {
                $target['cogs'] += $source['cogs'];
            }
            foreach (self::COST_FIELDS as $field) {
                if (! $source['direct_known'][$field]) {
                    $target['direct_known'][$field] = false;
                } else {
                    $target['direct_costs'][$field] += $source['direct_costs'][$field];
                }
            }
            if (! $source['profit_known']) {
                $target['profit_known'] = false;
            } else {
                $target['order_profit'] += $source['order_profit'];
            }
            $target['operating_expenses'] += $source['operating_expenses'];
            if (! $source['cancelled_costs_known']) {
                $target['cancelled_costs_known'] = false;
            } else {
                $target['cancelled_costs'] += $source['cancelled_costs'];
            }

            return $target;
        };
        $rangeBucket = static function (CarbonImmutable $from, CarbonImmutable $to) use ($today, $emptyBucket, $monthlyBuckets, $mergeBuckets): array {
            $bucket = $emptyBucket();
            $end = $to->greaterThan($today) ? $today : $to;
            for ($month = $from->startOfMonth(); $month->lessThanOrEqualTo($end); $month = $month->addMonthNoOverflow()) {
                $bucket = $mergeBuckets($bucket, $monthlyBuckets[$month->format('Y-m')] ?? $emptyBucket());
            }

            return $bucket;
        };
        $point = static function (CarbonImmutable $from, CarbonImmutable $to, string $label, array $bucket) use ($today): array {
            $cogs = $bucket['cogs_known'] ? $bucket['cogs'] : null;
            $directKnown = ! in_array(false, $bucket['direct_known'], true);
            $directTotal = $directKnown ? array_sum($bucket['direct_costs']) : null;
            $totalCosts = $cogs === null || $directTotal === null ? null : $cogs + $directTotal + $bucket['operating_expenses'];
            $profit = ! $bucket['profit_known'] || ! $bucket['cancelled_costs_known']
                ? null
                : $bucket['order_profit'] - $bucket['operating_expenses'] - $bucket['cancelled_costs'];
            $revenue = $bucket['revenue'];

            return [
                'label' => $label,
                'from' => $from->toDateString(),
                'to' => ($to->greaterThan($today) ? $today : $to)->toDateString(),
                'revenue' => $revenue,
                'order_count' => $bucket['order_count'],
                'cogs' => $cogs,
                'direct_costs' => $directTotal,
                'cost_components' => [
                    'cogs' => $cogs,
                    'shipping_cost_actual' => $bucket['direct_known']['shipping_cost_actual'] ? $bucket['direct_costs']['shipping_cost_actual'] : null,
                    'packaging_cost_actual' => $bucket['direct_known']['packaging_cost_actual'] ? $bucket['direct_costs']['packaging_cost_actual'] : null,
                    'payment_gateway_fee_actual' => $bucket['direct_known']['payment_gateway_fee_actual'] ? $bucket['direct_costs']['payment_gateway_fee_actual'] : null,
                    'other_direct_cost' => $bucket['direct_known']['other_direct_cost'] ? $bucket['direct_costs']['other_direct_cost'] : null,
                    'operating_expenses' => $bucket['operating_expenses'],
                ],
                'operating_expenses' => $bucket['operating_expenses'],
                'total_costs' => $totalCosts,
                'profit' => $profit,
                'margin' => $profit === null || $revenue <= 0 ? null : round($profit / $revenue * 100, 2),
                'partial' => $to->greaterThan($today),
                'state' => 'available',
            ];
        };
        $futurePoint = static fn (CarbonImmutable $from, CarbonImmutable $to, string $label): array => [
            'label' => $label, 'from' => $from->toDateString(), 'to' => $to->toDateString(),
            'revenue' => null, 'order_count' => null, 'cogs' => null, 'direct_costs' => null,
            'cost_components' => array_fill_keys(['cogs', ...self::COST_FIELDS, 'operating_expenses'], null),
            'operating_expenses' => null, 'total_costs' => null, 'profit' => null, 'margin' => null,
            'partial' => false, 'state' => 'future',
        ];
        $buildPoint = static function (CarbonImmutable $from, CarbonImmutable $to, string $label) use ($today, $futurePoint, $point, $rangeBucket): array {
            return $from->startOfDay()->greaterThan($today->startOfDay())
                ? $futurePoint($from, $to, $label)
                : $point($from, $to, $label, $rangeBucket($from, $to));
        };

        $monthly = [];
        for ($month = 1; $month <= 12; $month++) {
            $from = CarbonImmutable::create($selectedYear, $month, 1, 0, 0, 0, config('app.display_timezone', 'Asia/Ho_Chi_Minh'))->startOfDay();
            $monthly[] = $buildPoint($from, $from->endOfMonth(), 'Tháng '.$from->format('m/Y'));
        }
        $quarterly = [];
        for ($quarter = 1; $quarter <= 4; $quarter++) {
            $from = CarbonImmutable::create($selectedYear, (($quarter - 1) * 3) + 1, 1, 0, 0, 0, config('app.display_timezone', 'Asia/Ho_Chi_Minh'))->startOfDay();
            $quarterly[] = $buildPoint($from, $from->endOfQuarter(), 'Quý '.$quarter.'/'.$selectedYear);
        }
        $yearly = [];
        for ($year = $firstYear; $year <= $lastYear; $year++) {
            $from = CarbonImmutable::create($year, 1, 1, 0, 0, 0, config('app.display_timezone', 'Asia/Ho_Chi_Minh'))->startOfDay();
            $yearly[] = $buildPoint($from, $from->endOfYear(), (string) $year);
        }

        $monthStart = CarbonImmutable::create($selectedYear, $anchor->month, 1, 0, 0, 0, config('app.display_timezone', 'Asia/Ho_Chi_Minh'))->startOfDay();
        $quarterStart = CarbonImmutable::create($selectedYear, ((int) floor(($anchor->month - 1) / 3) * 3) + 1, 1, 0, 0, 0, config('app.display_timezone', 'Asia/Ho_Chi_Minh'))->startOfDay();
        $yearStart = CarbonImmutable::create($selectedYear, 1, 1, 0, 0, 0, config('app.display_timezone', 'Asia/Ho_Chi_Minh'))->startOfDay();
        $monthCurrent = $buildPoint($monthStart, $monthStart->endOfMonth(), $monthStart->format('m/Y'));
        $monthPreviousStart = $monthStart->subMonthNoOverflow();
        $monthPrevious = $buildPoint($monthPreviousStart, $monthPreviousStart->endOfMonth(), $monthPreviousStart->format('m/Y'));
        $quarterCurrent = $buildPoint($quarterStart, $quarterStart->endOfQuarter(), 'Quý '.$quarterStart->quarter.'/'.$selectedYear);
        $quarterPreviousStart = $quarterStart->subQuarterNoOverflow();
        $quarterPrevious = $buildPoint($quarterPreviousStart, $quarterPreviousStart->endOfQuarter(), 'Quý '.$quarterPreviousStart->quarter.'/'.$quarterPreviousStart->year);
        $yearCurrent = $buildPoint($yearStart, $yearStart->endOfYear(), (string) $selectedYear);
        $yearPreviousStart = $yearStart->subYear();
        $yearPrevious = $buildPoint($yearPreviousStart, $yearPreviousStart->endOfYear(), (string) $yearPreviousStart->year);

        return [
            'monthly' => $monthly,
            'quarterly' => $quarterly,
            'yearly' => $yearly,
            'month_comparison' => $this->comparison($monthCurrent, $monthPrevious, $monthStart->format('m/Y'), $monthPreviousStart->format('m/Y')),
            'quarter_comparison' => $this->comparison($quarterCurrent, $quarterPrevious, 'Quý '.$quarterStart->quarter.'/'.$selectedYear, 'Quý '.$quarterPreviousStart->quarter.'/'.$quarterPreviousStart->year),
            'year_comparison' => $this->comparison($yearCurrent, $yearPrevious, (string) $selectedYear, (string) $yearPreviousStart->year),
        ];
    }

    public function calculateTax(string $from, string $to, array $totals): array
    {
        return app(TaxComplianceService::class)->calculatePeriodTaxes($from, $to, $totals);
    }
}
