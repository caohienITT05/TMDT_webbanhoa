<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    /**
     * Reserve available stock once for a newly-created order.
     * Call from the order creation transaction so stock and order commit together.
     *
     * @param array<int, array{product_id: int|string|null, quantity: int|string}> $items
     */
    public function reserveForOrder(Order $order, array $items): bool
    {
        if (! Schema::hasColumn('orders', 'inventory_reserved_at')) {
            Log::warning('Inventory reservation schema is not installed; checkout continues without changing stock.', [
                'order_id' => $order->id,
            ]);

            return false;
        }

        return DB::transaction(function () use ($order, $items): bool {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            if ($lockedOrder->inventory_reserved_at !== null) {
                return false;
            }

            if ($lockedOrder->inventory_released_at !== null) {
                throw ValidationException::withMessages([
                    'stock' => 'Đơn hàng này đã giải phóng tồn kho và không thể giữ lại lần nữa.',
                ]);
            }

            $quantities = $this->quantitiesByProduct($items);
            if ($quantities === []) {
                throw ValidationException::withMessages([
                    'stock' => 'Đơn hàng không có sản phẩm hợp lệ để giữ tồn kho.',
                ]);
            }

            $products = Product::query()
                ->whereKey(array_keys($quantities))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($quantities as $productId => $quantity) {
                $product = $products->get($productId);
                if (! $product || ! $product->is_active || (int) $product->stock < $quantity) {
                    throw ValidationException::withMessages([
                        'stock' => 'Sản phẩm ' . ($product?->name ?? '#' . $productId) . ' không còn đủ số lượng trong kho.',
                    ]);
                }
            }

            foreach ($quantities as $productId => $quantity) {
                $updated = Product::query()
                    ->whereKey($productId)
                    ->where('stock', '>=', $quantity)
                    ->decrement('stock', $quantity);

                if ($updated !== 1) {
                    throw ValidationException::withMessages([
                        'stock' => 'Tồn kho vừa thay đổi; vui lòng kiểm tra lại số lượng sản phẩm.',
                    ]);
                }
            }

            $lockedOrder->forceFill([
                'inventory_reserved_at' => now(),
                'inventory_released_at' => null,
            ])->save();

            return true;
        });
    }

    /**
     * Restock only a newly-reserved COD order cancelled before completion.
     * PayPal inventory remains held because a pending provider transaction may still settle.
     */
    public function releaseForCancellation(Order $order, string $previousStatus): bool
    {
        if (! Schema::hasColumn('orders', 'inventory_reserved_at')) {
            return false;
        }

        if (strtolower((string) $order->payment_method) !== 'cod'
            || ! in_array($previousStatus, ['pending', 'confirmed', 'preparing', 'processing', 'shipping'], true)
            || in_array(strtolower((string) $order->payment_status), ['paid', 'completed'], true)) {
            return false;
        }

        return DB::transaction(function () use ($order): bool {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            if ($lockedOrder->inventory_reserved_at === null || $lockedOrder->inventory_released_at !== null) {
                return false;
            }

            $quantities = $this->quantitiesByProduct(
                $lockedOrder->items()->get(['product_id', 'quantity'])->toArray()
            );

            $products = Product::query()
                ->whereKey(array_keys($quantities))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($quantities as $productId => $quantity) {
                if ($products->has($productId)) {
                    Product::query()->whereKey($productId)->increment('stock', $quantity);
                }
            }

            $lockedOrder->forceFill(['inventory_released_at' => now()])->save();

            return true;
        });
    }

    /** @return array<int, int> */
    private function quantitiesByProduct(array $items): array
    {
        $quantities = [];
        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);
            if ($productId < 1 || $quantity < 1) {
                continue;
            }

            $quantities[$productId] = ($quantities[$productId] ?? 0) + $quantity;
        }

        ksort($quantities);

        return $quantities;
    }
}
