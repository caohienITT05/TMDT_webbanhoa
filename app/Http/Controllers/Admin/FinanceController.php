<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\FinanceAnalyticsService;
use App\Services\TaxComplianceService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FinanceController extends Controller
{
    private function money(bool $required = false): array
    {
        return [$required ? 'required' : 'nullable', 'numeric', 'min:0', 'max:9999999999999.99', 'decimal:0,2'];
    }

    public function index(Request $request, FinanceAnalyticsService $finance)
    {
        $input = $request->validate([
            'period' => ['nullable', Rule::in(['today', 'week', 'month', 'quarter', 'year', 'custom'])],
            'from_date' => ['required_if:period,custom', 'nullable', 'date_format:Y-m-d'],
            'to_date' => ['required_if:period,custom', 'nullable', 'date_format:Y-m-d', 'after_or_equal:from_date'],
        ]);
        $today = CarbonImmutable::today();
        $period = $input['period'] ?? 'year';
        $from = match ($period) {
            'today' => $today,
            'week' => $today->subDays(6),
            'month' => $today->startOfMonth(),
            'quarter' => $today->startOfQuarter(),
            'year' => $today->startOfYear(),
            'custom' => CarbonImmutable::parse($input['from_date']),
            default => $today->startOfYear(),
        };
        $to = $period === 'custom' ? CarbonImmutable::parse($input['to_date']) : $today;
        $from = $from->toDateString();
        $to = $to->toDateString();
        $actual = $finance->getPeriodSummary($from, $to);
        $forecast = $finance->getPeriodSummary($from, $to, true);
        $periodOrders = DB::table('orders')->whereBetween('created_at', [
            CarbonImmutable::parse($from)->startOfDay(),
            CarbonImmutable::parse($to)->endOfDay(),
        ]);
        $orderIds = (clone $periodOrders)->pluck('id');
        $itemCount = DB::table('order_items')->whereIn('order_id', $orderIds)->count();
        $dataCoverage = [
            'orders' => (clone $periodOrders)->count(),
            'completed_orders' => $actual['totals']['order_count'],
            'forecast_orders' => $forecast['totals']['order_count'],
            'cancelled_orders' => (clone $periodOrders)->where('order_status', 'cancelled')->count(),
            'items' => $itemCount,
            'items_with_cost' => DB::table('order_items')->whereIn('order_id', $orderIds)->whereNotNull('cost_price_snapshot')->count(),
            'orders_with_direct_costs' => DB::table('order_costs')->whereIn('order_id', $orderIds)->count(),
            'expenses' => DB::table('expenses')->whereBetween('expense_date', [$from, $to])->count(),
            'tax_records' => DB::table('tax_records')->where('period_start', '<=', $to)->where('period_end', '>=', $from)->count(),
            'products' => DB::table('products')->count(),
            'products_with_cost' => DB::table('products')->whereNotNull('cost_price')->count(),
        ];
        $taxCompliance = app(TaxComplianceService::class)->summary();
        $comparison = $finance->comparisonDashboard(
            $today,
            CarbonImmutable::parse($from),
            CarbonImmutable::parse($to),
        );

        return view('admin.finance.index', compact('actual', 'comparison', 'dataCoverage', 'forecast', 'from', 'to', 'period', 'taxCompliance'));
    }

    public function export(Request $request, FinanceAnalyticsService $finance)
    {
        $data = $request->validate([
            'from_date' => ['required', 'date_format:Y-m-d'],
            'to_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:from_date'],
        ]);
        $from = CarbonImmutable::createFromFormat('Y-m-d', $data['from_date'])->startOfDay();
        $to = CarbonImmutable::createFromFormat('Y-m-d', $data['to_date'])->endOfDay();
        $fromDate = $from->toDateString();
        $toDate = $to->toDateString();
        $actual = $finance->getPeriodSummary($fromDate, $toDate);
        $forecast = $finance->getPeriodSummary($fromDate, $toDate, true);
        $orders = Order::with('items')->whereBetween('created_at', [$from, $to])->oldest()->get();
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
            'shipping_cost_actual' => ['Ship thực tế', 'money'],
            'packaging_cost_actual' => ['Chi phí đóng gói', 'money'],
            'payment_gateway_fee_actual' => ['Phí thanh toán', 'money'],
            'other_direct_cost' => ['Chi phí trực tiếp khác', 'money'],
            'operating_expenses' => ['Chi phí vận hành', 'money'],
            'cancelled_costs' => ['Chi phí đơn hủy đã ghi nhận', 'money'],
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
            $write(['Mã đơn', 'Ngày tạo', 'Trạng thái', 'Doanh thu sản phẩm', 'Giảm giá', 'Doanh thu thuần sản phẩm', 'Ship khách trả', 'Giá vốn', 'Ship thực tế', 'Đóng gói', 'Phí thanh toán', 'Chi phí trực tiếp khác', 'Lợi nhuận trực tiếp', 'Tình trạng dữ liệu']);
            foreach ($orderRows as $row) {
                $order = $row['order'];
                $summary = $row['summary'];
                $write([
                    $order->order_code, $order->created_at->format('d/m/Y H:i'), $order->order_status,
                    $vnd($summary['product_revenue']), $vnd($summary['discount']), $vnd($summary['net_product_revenue']),
                    $vnd($summary['shipping_income']), $vnd($summary['cogs']), $vnd($summary['shipping_cost_actual']),
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

        return back()->with('success', 'Đã lưu chi phí thực tế.');
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
        $from = $profile ? $profile->tax_year.'-01-01' : now()->startOfYear()->toDateString();
        $yearEnd = $profile ? CarbonImmutable::create((int) $profile->tax_year)->endOfYear() : CarbonImmutable::today()->endOfYear();
        $to = CarbonImmutable::today()->min($yearEnd)->toDateString();
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

    public function saveProductTaxProfile(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'vat_category' => ['required', Rule::in(array_keys(TaxComplianceService::VAT_CATEGORIES))],
            'vat_rate' => ['nullable', 'required_if:vat_category,custom', 'numeric', 'between:0,100', 'decimal:0,4'],
            'price_includes_vat' => ['required', 'boolean'],
            'effective_from' => ['required', 'date_format:Y-m-d'],
            'effective_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:effective_from'],
            'note' => ['nullable', 'string', 'max:5000'],
        ]);
        if ($data['vat_category'] !== 'custom') {
            $data['vat_rate'] = TaxComplianceService::VAT_CATEGORIES[$data['vat_category']]['rate'];
        }
        $data['updated_at'] = now();
        if (! DB::table('product_tax_profiles')->where('product_id', $data['product_id'])->exists()) {
            $data['created_at'] = now();
        }
        DB::table('product_tax_profiles')->updateOrInsert(['product_id' => $data['product_id']], $data);

        return back()->with('success', 'Đã lưu phân loại VAT của sản phẩm.');
    }
}
