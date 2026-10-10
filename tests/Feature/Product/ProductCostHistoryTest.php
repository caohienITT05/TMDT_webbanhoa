<?php

namespace Tests\Feature\Product;

use App\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesBloomGiftData;
use Tests\TestCase;

class ProductCostHistoryTest extends TestCase
{
    use CreatesBloomGiftData, RefreshDatabase {
        CreatesBloomGiftData::afterRefreshingDatabase insteadof RefreshDatabase;
    }

    public function test_cost_change_creates_history_and_only_new_orders_use_the_new_cost(): void
    {
        $admin = $this->createAdmin();
        $product = $this->createProduct(['price' => 500000, 'cost_price' => 280000]);
        $oldOrder = $this->createOrder();
        $oldItem = OrderItem::create([
            'order_id' => $oldOrder->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 500000,
            'quantity' => 1,
            'subtotal' => 500000,
        ]);

        $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'name' => $product->name,
            'category_id' => $product->category_id,
            'price' => 500000,
            'cost_price' => 320000,
            'cost_change_reason' => 'Phiếu nhập mới',
            'season' => $product->season,
            'stock' => $product->stock,
            'description' => $product->description,
            'is_active' => 1,
        ])->assertSessionHasNoErrors();

        $newOrder = $this->createOrder();
        $newItem = OrderItem::create([
            'order_id' => $newOrder->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 500000,
            'quantity' => 1,
            'subtotal' => 500000,
        ]);

        $this->assertEquals('320000.00', $product->fresh()->cost_price);
        $this->assertEquals('280000.00', $oldItem->fresh()->cost_price_snapshot);
        $this->assertEquals('320000.00', $newItem->fresh()->cost_price_snapshot);
        $this->assertDatabaseHas('product_cost_histories', [
            'product_id' => $product->id,
            'old_cost_price' => 280000,
            'new_cost_price' => 320000,
            'changed_by' => $admin->id,
            'reason' => 'Phiếu nhập mới',
        ]);

        $this->put(route('admin.products.update', $product), [
            'name' => $product->name,
            'category_id' => $product->category_id,
            'price' => 500000,
            'cost_price' => 320000,
            'season' => $product->season,
            'stock' => $product->stock,
            'description' => $product->description,
            'is_active' => 1,
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('product_cost_histories', 1);
    }
}
