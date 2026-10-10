<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductCostHistory;
use App\Services\FinanceAnalyticsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillFinance extends Command
{
    protected $signature = 'bloomgift:backfill-finance {--dry-run : Chỉ báo cáo, không ghi dữ liệu}';

    protected $description = 'Backfill snapshot và hoàn tất Finance theo cách idempotent';

    public function handle(FinanceAnalyticsService $finance): int
    {
        $missingProducts = DB::table('products')->whereNull('cost_price')->orWhere('cost_price', '<=', 0)->count();
        $missingItems = OrderItem::whereNull('cost_price_snapshot')->orWhere('cost_price_snapshot', '<=', 0)->count();
        $completed = Order::where('order_status', 'completed')->get();
        $completedIds = $completed->modelKeys();
        $missingOrderCosts = DB::table('orders')->where('order_status', 'completed')
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('order_costs')->whereColumn('order_costs.order_id', 'orders.id'))
            ->count();
        $notFinalized = DB::table('orders')->where('order_status', 'completed')
            ->leftJoin('order_costs', 'order_costs.order_id', '=', 'orders.id')
            ->whereNull('order_costs.financial_finalized_at')->count();

        $this->table(['Kiểm tra', 'Số lượng'], [
            ['Product thiếu cost', $missingProducts],
            ['OrderItem thiếu snapshot', $missingItems],
            ['Order completed thiếu OrderCost', $missingOrderCosts],
            ['Order completed chưa finalize', $notFinalized],
        ]);
        if ($this->option('dry-run')) {
            $this->comment('Dry-run: không có dữ liệu nào được thay đổi.');

            return self::SUCCESS;
        }

        OrderItem::with(['order', 'product'])->where(function ($query) {
            $query->whereNull('cost_price_snapshot')->orWhere('cost_price_snapshot', '<=', 0);
        })->orderBy('id')->each(function (OrderItem $item): void {
            if (! $item->order || ! $item->product) {
                return;
            }
            $history = ProductCostHistory::where('product_id', $item->product_id)
                ->where('effective_at', '<=', $item->order->created_at)
                ->latest('effective_at')->first();
            $cost = $history?->new_cost_price ?? $item->product->cost_price;
            if ($cost === null || (float) $cost <= 0) {
                return;
            }
            DB::transaction(function () use ($cost, $history, $item): void {
                $locked = OrderItem::whereKey($item->id)->lockForUpdate()->firstOrFail();
                if ($locked->cost_price_snapshot !== null && (float) $locked->cost_price_snapshot > 0) {
                    return;
                }
                $locked->timestamps = false;
                $locked->update([
                    'cost_price_snapshot' => $cost,
                    'cost_price_snapshot_source' => $history ? 'backfill_history' : 'backfill_current_product',
                ]);
            });
        });

        Order::whereIn('id', $completedIds)->orderBy('id')->each(fn (Order $order) => $finance->finalizeOrderFinancials($order));
        $this->info('Backfill hoàn tất. Có thể chạy lại an toàn; dữ liệu manual và snapshot đã có không bị ghi đè.');

        return self::SUCCESS;
    }
}
