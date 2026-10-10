<?php

namespace App\Services;

use App\Models\Order;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TaxComplianceService
{
    public const ENTERPRISE_CIT_EXEMPT_REVENUE = 1_000_000_000;

    public const ENTERPRISE_CIT_15_REVENUE = 3_000_000_000;

    public const ENTERPRISE_CIT_17_REVENUE = 50_000_000_000;

    public const CIT_STATUSES = [
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
        return Order::query()->whereBetween('created_at', [
            CarbonImmutable::create($year)->startOfYear(),
            CarbonImmutable::create($year)->endOfYear(),
        ])->get(['id', 'status', 'order_status', 'payment_status', 'total_amount', 'total'])
            ->reject(function (Order $order) {
                return in_array(strtolower((string) $order->status), ['cancelled', 'canceled'], true)
                    || in_array(strtolower((string) $order->order_status), ['cancelled', 'canceled'], true);
            })->filter(function (Order $order) {
                return strtolower((string) $order->payment_status) === 'paid'
                    || in_array(strtolower((string) $order->status), ['completed', 'delivered', 'confirmed'], true)
                    || in_array(strtolower((string) $order->order_status), ['completed', 'delivered', 'confirmed'], true);
            });
    }

    public function recognizedRevenueForYear(int $year): float
    {
        return (float) $this->recognizedOrdersForYear($year)
            ->sum(fn (Order $order) => (float) ($order->total_amount ?? $order->total));
    }

    public function corporateIncomeTaxEvaluation(?object $profile = null): array
    {
        $profile ??= $this->profile();
        if (! $profile || $profile->entity_type !== 'enterprise') {
            return [
                'status' => 'taxable',
                'status_label' => self::CIT_STATUSES['taxable'],
                'applicable_rate' => 20.0,
                'current_revenue' => null,
                'projected_revenue' => null,
                'actual_revenue' => null,
                'prior_year_revenue' => null,
                'assessment_revenue' => null,
                'eligible' => false,
                'reason' => 'Chưa có hồ sơ doanh nghiệp hợp lệ để đánh giá tự động.',
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
            'cit_reference' => 'Luật 67/2025/QH15; Nghị định 320/2025/NĐ-CP; Nghị định 141/2026/NĐ-CP',
            'vat_reference' => 'Luật 48/2024/QH15; Nghị quyết 204/2025/QH15',
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
                $amounts[$category]['estimated'] += $catalogVat['tax'];

                continue;
            }

            $base = $totals[$rule->tax_base] ?? null;
            if ($base === null) {
                $amounts[$category]['estimated_complete'] = false;

                continue;
            }
            $rate = (float) $rule->rate;
            if ($rule->calculation_method === 'corporate_income_2026') {
                if ($evaluation['status'] === 'estimated_exempt' || $evaluation['status'] === 'confirmed_exempt') {
                    $amounts[$category]['estimated'] += 0;

                    continue;
                }
                $rate = $evaluation['applicable_rate'];
            }
            $amounts[$category]['estimated'] += $rule->calculation_method === 'inclusive'
                ? (int) round(max(0, $base) * $rate / (100 + $rate))
                : (int) round(max(0, $base) * $rate / 100);
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
            'cit_status' => $evaluation['status'],
            'cit_rate' => $evaluation['applicable_rate'],
        ];
    }

    public function estimateCatalogOutputVat(string $from, string $to): array
    {
        $profile = $this->profile();
        if (! $profile) {
            return ['tax' => 0, 'taxable_base' => 0, 'unclassified_base' => 0];
        }

        $orders = Order::with('items')->whereBetween('created_at', [
            CarbonImmutable::parse($from)->startOfDay(),
            CarbonImmutable::parse($to)->endOfDay(),
        ])->where('order_status', 'completed')->get();
        $mappings = DB::table('product_tax_profiles')->get()->keyBy('product_id');
        $groups = [];
        $unclassifiedBase = 0;

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
                $mapping = $mappings->get($line['item']->product_id);
                $mappingActive = $mapping
                    && $mapping->effective_from <= $order->created_at->toDateString()
                    && ($mapping->effective_to === null || $mapping->effective_to >= $order->created_at->toDateString());
                if ($mappingActive) {
                    $preset = self::VAT_CATEGORIES[$mapping->vat_category] ?? self::VAT_CATEGORIES['custom'];
                    $rate = $mapping->vat_category === 'custom' ? (float) $mapping->vat_rate : (float) $preset['rate'];
                    $included = (bool) $mapping->price_includes_vat;
                } else {
                    $rate = (float) $profile->default_vat_rate;
                    $included = (bool) $profile->prices_include_vat;
                    $unclassifiedBase += $base;
                }
                $addBase($groups, $rate, $included, $base);
            }

            $addBase(
                $groups,
                (float) $profile->default_vat_rate,
                (bool) $profile->prices_include_vat,
                self::cents($order->shipping_fee)
            );
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

        return ['tax' => $tax, 'taxable_base' => $taxableBase, 'unclassified_base' => $unclassifiedBase];
    }
}
