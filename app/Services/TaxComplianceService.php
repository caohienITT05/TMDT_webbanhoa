<?php

namespace App\Services;

use App\Models\Order;
use App\Support\FinanceDateRange;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TaxComplianceService
{
    public const ENTERPRISE_CIT_EXEMPT_REVENUE = 1_000_000_000;

    public const ENTERPRISE_CIT_15_REVENUE = 3_000_000_000;

    public const ENTERPRISE_CIT_17_REVENUE = 50_000_000_000;

    public const CIT_STATUSES = [
        'unconfigured' => 'Chưa cấu hình hồ sơ thuế',
        'estimated_exempt' => 'Dự kiến được miễn',
        'confirmed_exempt' => 'Đã xác nhận miễn',
        'taxable' => 'Thuộc diện chịu thuế',
    ];

    public const VAT_CATEGORIES = [
        'not_taxable' => ['label' => 'Không chịu thuế', 'rate' => 0],
        'zero' => ['label' => 'Thuế suất 0%', 'rate' => 0],
        'five' => ['label' => 'Thuế suất 5%', 'rate' => 5],
        'eight' => ['label' => 'Thuế suất 8%', 'rate' => 8],
        'ten' => ['label' => 'Thuế suất 10%', 'rate' => 10],
        'custom' => ['label' => 'Tùy chỉnh', 'rate' => null],
    ];

    private static function cents(mixed $amount): int
    {
        return (int) round((float) $amount * 100);
    }

    public function profile(): ?object
    {
        return DB::table('tax_profiles')->where('store_key', 'default')->first();
    }

    private function recognizedOrdersForYear(int $year): Collection
    {
        [$start, $endExclusive] = FinanceDateRange::utcBounds($year.'-01-01', $year.'-12-31');

        return Order::query()
            ->financiallyValid()
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $endExclusive)
            ->get(['id', 'status', 'order_status', 'payment_status', 'total_amount', 'total'])
            ->filter(function (Order $order) {
                $effectiveStatus = strtolower((string) ($order->order_status ?: $order->status));

                return strtolower((string) $order->payment_status) === 'paid'
                    || in_array($effectiveStatus, ['completed', 'delivered', 'confirmed'], true);
            });
    }

    public function recognizedRevenueForYear(int $year): float
    {
        return (float) $this->recognizedOrdersForYear($year)
            ->sum(fn (Order $order) => (float) FinanceAnalyticsService::orderTotal($order));
    }

    public function corporateIncomeTaxEvaluation(?object $profile = null): array
    {
        $profile ??= $this->profile();
        if (! $profile || $profile->entity_type !== 'enterprise') {
            return [
                'status' => 'unconfigured',
                'status_label' => self::CIT_STATUSES['unconfigured'],
                'applicable_rate' => null,
                'current_revenue' => null,
                'projected_revenue' => null,
                'actual_revenue' => null,
                'prior_year_revenue' => null,
                'assessment_revenue' => null,
                'eligible' => false,
                'cit_reduction_status' => 'not_applicable',
                'cit_reduction_percent' => 0,
                'cit_reduction_revenue' => null,
                'reason' => 'Chưa có hồ sơ doanh nghiệp hợp lệ để xác định mức thuế hoặc ưu đãi.',
            ];
        }

        $current = $this->recognizedRevenueForYear((int) $profile->tax_year);
        $projected = $profile->projected_annual_revenue ?? $profile->declared_annual_revenue ?? $current;
        $actual = $profile->actual_annual_revenue === null ? null : (float) $profile->actual_annual_revenue;
        $prior = $profile->prior_year_revenue === null ? null : (float) $profile->prior_year_revenue;
        $assessment = $actual ?? ($profile->is_new_business ? (float) $projected : ($prior ?? (float) $projected));
        $eligible = (bool) $profile->eligible_small_enterprise && ! (bool) $profile->has_non_qualifying_related_party;

        $rate = match (true) {
            ! $eligible => 20.0,
            $assessment <= self::ENTERPRISE_CIT_15_REVENUE => 15.0,
            $assessment <= self::ENTERPRISE_CIT_17_REVENUE => 17.0,
            default => 20.0,
        };
        $exempt = $eligible && $assessment <= self::ENTERPRISE_CIT_EXEMPT_REVENUE;
        $status = $exempt ? 'estimated_exempt' : 'taxable';
        $taxYear = (int) $profile->tax_year;
        $splitStatusAvailable = \Illuminate\Support\Facades\Schema::hasColumn('tax_profiles', 'is_split_from_large_enterprise');
        $splitFromLargeEnterprise = $splitStatusAvailable ? $profile->is_split_from_large_enterprise : null;
        $reductionRevenue = $actual ?? (float) $projected;
        $citReductionStatus = match (true) {
            ! in_array($taxYear, [2026, 2027], true) => 'not_applicable',
            $reductionRevenue > 10_000_000_000 => 'over_revenue_limit',
            $splitFromLargeEnterprise === null => 'needs_confirmation',
            (bool) $splitFromLargeEnterprise => 'excluded_split',
            default => 'eligible_estimate',
        };

        return [
            'status' => $status,
            'status_label' => self::CIT_STATUSES[$status],
            'applicable_rate' => $rate,
            'current_revenue' => $current,
            'projected_revenue' => (float) $projected,
            'actual_revenue' => $actual,
            'prior_year_revenue' => $prior,
            'assessment_revenue' => $assessment,
            'eligible' => $eligible,
            'cit_reduction_status' => $citReductionStatus,
            'cit_reduction_percent' => $citReductionStatus === 'eligible_estimate' ? 30 : 0,
            'cit_reduction_revenue' => $reductionRevenue,
            'reason' => $exempt
                ? 'Doanh thu dùng để đánh giá chưa vượt 1 tỷ đồng và hồ sơ demo đang đáp ứng điều kiện miễn.'
                : ($eligible ? 'Doanh thu hoặc kết quả quyết toán không còn thuộc ngưỡng miễn 1 tỷ đồng.' : 'Hồ sơ có điều kiện loại trừ nên không tự áp dụng miễn thuế hoặc mức 15%/17%.'),
        ];
    }

    public function corporateIncomeTaxRate(?object $profile = null): ?float
    {
        return $this->corporateIncomeTaxEvaluation($profile)['applicable_rate'] ?? null;
    }

    public function summary(): array
    {
        $profile = $this->profile();
        $evaluation = $this->corporateIncomeTaxEvaluation($profile);
        if ($profile) {
            $confirmed = DB::table('tax_records')
                ->where('tax_type', 'tndn_doanh_nghiep_2026')
                ->where('period_start', $profile->tax_year.'-01-01')
                ->where('period_end', $profile->tax_year.'-12-31')
                ->where('status', 'confirmed_exempt')
                ->exists();
            if ($confirmed && $evaluation['status'] === 'estimated_exempt') {
                $evaluation['status'] = 'confirmed_exempt';
                $evaluation['status_label'] = self::CIT_STATUSES['confirmed_exempt'];
            }
        }

        $productCount = DB::table('products')->count();
        $mappedProducts = DB::table('product_tax_profiles')->count();
        $recognizedOrders = $profile ? $this->recognizedOrdersForYear((int) $profile->tax_year)->count() : 0;

        return [
            'profile' => $profile,
            'cit' => $evaluation,
            'mapped_products' => $mappedProducts,
            'unclassified_products' => max(0, $productCount - $mappedProducts),
            'einvoice_order_count' => $recognizedOrders,
            'cit_reference' => 'Luật 67/2025/QH15, sửa đổi bởi Luật 09/2026/QH16; Nghị định 320/2025/NĐ-CP; Nghị định 141/2026/NĐ-CP; Nghị quyết 43/2026/QH16',
            'vat_reference' => 'Luật 48/2024/QH15, sửa đổi bởi Luật 09/2026/QH16 và Luật 149/2025/QH15; Nghị quyết 204/2025/QH15',
            'einvoice_reference' => 'Nghị định 70/2025/NĐ-CP về hóa đơn điện tử',
        ];
    }

    public function calculatePeriodTaxes(string $from, string $to, array $totals): array
    {
        $records = DB::table('tax_records')->where('period_start', $from)->where('period_end', $to)->get()->keyBy('tax_type');
        $rules = DB::table('tax_settings')->where('is_active', true)->where('effective_from', '<=', $to)
            ->where(fn ($query) => $query->whereNull('effective_to')->orWhere('effective_to', '>=', $from))->get()->groupBy('tax_type');
        $types = $records->keys()->merge($rules->keys())->unique();
        $empty = [
            'estimated_tax' => null,
            'actual_tax' => null,
            'estimated_indirect_tax' => null,
            'actual_indirect_tax' => null,
            'estimated_indirect_tax_base' => null,
            'cit_status' => null,
            'cit_rate' => null,
            'unclassified_indirect_tax_base' => null,
        ];
        if ($types->isEmpty()) {
            return $empty;
        }

        $amounts = [
            'income' => ['estimated' => 0, 'actual' => 0, 'has_type' => false, 'estimated_complete' => true, 'actual_complete' => true],
            'indirect' => ['estimated' => 0, 'actual' => 0, 'has_type' => false, 'estimated_complete' => true, 'actual_complete' => true],
        ];
        $evaluation = $this->corporateIncomeTaxEvaluation();
        $catalogVat = null;

        foreach ($types as $type) {
            $record = $records->get($type);
            $candidateRules = $rules->get($type, collect());
            $firstRule = $candidateRules->first();
            $category = $record->tax_category ?? $firstRule?->tax_category ?? 'income';
            $category = in_array($category, ['income', 'indirect'], true) ? $category : 'income';
            $amounts[$category]['has_type'] = true;

            if ($record?->status === 'confirmed_exempt' && $category === 'income') {
                $amounts[$category]['actual'] += 0;
            } elseif (! $record || ! in_array($record->status, ['confirmed', 'paid'], true) || $record->actual_tax === null) {
                $amounts[$category]['actual_complete'] = false;
            } else {
                $amounts[$category]['actual'] += self::cents($record->actual_tax);
            }

            if ($record) {
                $amounts[$category]['estimated'] += self::cents($record->estimated_tax);

                continue;
            }

            $covering = $candidateRules->filter(fn ($rule) => $rule->effective_from <= $from && ($rule->effective_to === null || $rule->effective_to >= $to));
            if ($covering->count() !== 1 || $candidateRules->count() !== 1) {
                $amounts[$category]['estimated_complete'] = false;

                continue;
            }

            $rule = $covering->first();
            if ($rule->calculation_method === 'catalog_vat_2026') {
                $catalogVat ??= $this->estimateCatalogOutputVat($from, $to);
                if ($catalogVat['tax'] === null) {
                    $amounts[$category]['estimated_complete'] = false;
                } else {
                    $amounts[$category]['estimated'] += $catalogVat['tax'];
                }
                continue;
            }

            if ($rule->calculation_method === 'corporate_income_2026' && $evaluation['status'] === 'unconfigured') {
                $amounts[$category]['estimated_complete'] = false;

                continue;
            }

            $base = $totals[$rule->tax_base] ?? null;
            if ($base === null) {
                $amounts[$category]['estimated_complete'] = false;

                continue;
            }
            $rate = (float) $rule->rate;
            $reductionPercent = 0;
            if ($rule->calculation_method === 'corporate_income_2026') {
                if ($evaluation['status'] === 'estimated_exempt' || $evaluation['status'] === 'confirmed_exempt') {
                    $amounts[$category]['estimated'] += 0;
                    continue;
                }
                $rate = $evaluation['applicable_rate'];
                $reductionPercent = $evaluation['cit_reduction_percent'] ?? 0;
            }
            $estimatedAmount = $rule->calculation_method === 'inclusive'
                ? (int) round(max(0, $base) * $rate / (100 + $rate))
                : (int) round(max(0, $base) * $rate / 100);
            if ($reductionPercent > 0) {
                $estimatedAmount = (int) round($estimatedAmount * (100 - $reductionPercent) / 100);
            }
            $amounts[$category]['estimated'] += $estimatedAmount;
        }

        $value = static function (array $bucket, string $kind): ?int {
            return $bucket['has_type'] && $bucket[$kind.'_complete'] ? $bucket[$kind] : null;
        };

        return [
            'estimated_tax' => $value($amounts['income'], 'estimated'),
            'actual_tax' => $value($amounts['income'], 'actual'),
            'estimated_indirect_tax' => $value($amounts['indirect'], 'estimated'),
            'actual_indirect_tax' => $value($amounts['indirect'], 'actual'),
            'estimated_indirect_tax_base' => $catalogVat['taxable_base'] ?? null,
            'unclassified_indirect_tax_base' => $catalogVat['unclassified_base'] ?? null,
            'cit_status' => $evaluation['status'],
            'cit_rate' => $evaluation['applicable_rate'],
        ];
    }

    public function estimateCatalogOutputVat(string $from, string $to): array
    {
        $profile = $this->profile();
        if (! $profile) {
            return ['tax' => null, 'taxable_base' => null, 'unclassified_base' => null, 'complete' => false];
        }

        [$start, $endExclusive] = FinanceDateRange::utcBounds($from, $to);
        $orders = Order::with('items')
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $endExclusive)
            ->completedOrDelivered()
            ->get();
        $mappings = DB::table('product_tax_profiles')->get()->keyBy('product_id');
        $policyRules = Schema::hasTable('vat_policy_rules')
            ? DB::table('vat_policy_rules')->get()->map(function ($rule) {
                $rule->product_ids = json_decode((string) ($rule->product_ids ?? '[]'), true) ?: [];

                return $rule;
            })
            : collect();
        $productIds = $orders->flatMap(fn ($order) => $order->items->pluck('product_id'))->filter()->unique()->values();
        $productCategories = $productIds->isEmpty()
            ? collect()
            : DB::table('products')->whereIn('id', $productIds)->pluck('category_id', 'id');
        $groups = [];
        $unclassifiedBase = 0;
        $hasUnclassifiedAmount = false;

        $addBase = static function (array &$groups, float $rate, bool $included, int $base): void {
            $key = number_format($rate, 4, '.', '').'|'.($included ? '1' : '0');
            $groups[$key] ??= ['rate' => $rate, 'included' => $included, 'base' => 0];
            $groups[$key]['base'] += max(0, $base);
        };

        foreach ($orders as $order) {
            $lines = $order->items->map(fn ($item) => [
                'item' => $item,
                'gross' => self::cents($item->price) * $item->quantity,
            ])->values();
            $grossTotal = $lines->sum('gross');
            $netTotal = max(0, $grossTotal - self::cents($order->discount));
            $remaining = $netTotal;

            foreach ($lines as $index => $line) {
                $base = $index === $lines->count() - 1
                    ? $remaining
                    : min($remaining, (int) round($netTotal * ($grossTotal > 0 ? $line['gross'] / $grossTotal : 0)));
                $remaining -= $base;
                $productId = (int) $line['item']->product_id;
                $categoryId = $productCategories->get($productId);
                $orderDate = $order->created_at_local->toDateString();
                $legacyMapping = $mappings->get($productId);
                $legacyMappingActive = $legacyMapping
                    && $legacyMapping->effective_from <= $orderDate
                    && ($legacyMapping->effective_to === null || $legacyMapping->effective_to >= $orderDate);
                $matchingRules = $policyRules->filter(function ($rule) use ($productId, $categoryId, $orderDate): bool {
                    if ($rule->effective_from > $orderDate || ($rule->effective_to !== null && $rule->effective_to < $orderDate)) {
                        return false;
                    }

                    return match ($rule->scope) {
                        'products' => in_array($productId, array_map('intval', $rule->product_ids), true),
                        'category' => $categoryId !== null && (int) $rule->category_id === (int) $categoryId,
                        'store' => true,
                        default => false,
                    };
                });
                $specificity = ['store' => 1, 'category' => 2, 'products' => 3];
                $selectedRule = null;
                $ruleConflict = false;
                if ($matchingRules->isNotEmpty()) {
                    $highest = $matchingRules->max(fn ($rule) => $specificity[$rule->scope] ?? 0);
                    if ($highest < 3 && $legacyMappingActive) {
                        $selectedRule = $legacyMapping;
                    } else {
                        $mostSpecific = $matchingRules->filter(fn ($rule) => ($specificity[$rule->scope] ?? 0) === $highest)
                            ->sortByDesc('effective_from')->values();
                        $latestDate = $mostSpecific->first()->effective_from;
                        $latestRules = $mostSpecific->filter(fn ($rule) => $rule->effective_from === $latestDate);
                        $ruleConflict = $latestRules->count() > 1;
                        $selectedRule = $ruleConflict ? null : $latestRules->first();
                    }
                } elseif ($legacyMappingActive) {
                    $selectedRule = $legacyMapping;
                }

                if ($ruleConflict) {
                    $unclassifiedBase += $base;
                    $hasUnclassifiedAmount = true;
                    continue;
                }

                $mapping = $selectedRule;
                $mappingActive = $mapping
                    && $mapping->effective_from <= $orderDate
                    && ($mapping->effective_to === null || $mapping->effective_to >= $orderDate);
                if ($mappingActive) {
                    $preset = self::VAT_CATEGORIES[$mapping->vat_category] ?? self::VAT_CATEGORIES['custom'];
                    $rate = $mapping->vat_category === 'custom' ? (float) $mapping->vat_rate : (float) $preset['rate'];
                    $included = (bool) $mapping->price_includes_vat;
                } else {
                    $unclassifiedBase += $base;
                    $hasUnclassifiedAmount = true;
                    continue;
                }
                $addBase($groups, $rate, $included, $base);
            }

            $shippingIncome = self::cents($order->shipping_fee);
            $giftFeesKnown = $order->gift_card_fee !== null && $order->gift_wrap_fee !== null;
            $giftServiceIncome = $giftFeesKnown
                ? self::cents($order->gift_card_fee) + self::cents($order->gift_wrap_fee)
                : 0;
            $unallocated = self::cents(FinanceAnalyticsService::orderTotal($order)) - $netTotal - $shippingIncome - $giftServiceIncome;
            if ($giftServiceIncome > 0) {
                $unclassifiedBase += $giftServiceIncome;
                $hasUnclassifiedAmount = true;
            }
            if ($unallocated !== 0 || ! $giftFeesKnown && $unallocated > 0) {
                $unclassifiedBase += max(0, $unallocated);
                $hasUnclassifiedAmount = true;
            }

            $addBase($groups, (float) $profile->default_vat_rate, (bool) $profile->prices_include_vat, self::cents($order->shipping_fee));
        }

        $tax = 0;
        $taxableBase = 0;
        foreach ($groups as $group) {
            if ($group['rate'] <= 0) {
                continue;
            }
            $taxableBase += $group['base'];
            $tax += $group['included']
                ? (int) round($group['base'] * $group['rate'] / (100 + $group['rate']))
                : (int) round($group['base'] * $group['rate'] / 100);
        }

        return [
            'tax' => $hasUnclassifiedAmount ? null : $tax,
            'taxable_base' => $taxableBase,
            'unclassified_base' => $unclassifiedBase,
            'complete' => ! $hasUnclassifiedAmount,
        ];
    }
}
