<?php

namespace Tests\Feature\Product;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesBloomGiftData;
use Tests\TestCase;

class ProductSeasonTest extends TestCase
{
    use CreatesBloomGiftData, RefreshDatabase {
        CreatesBloomGiftData::afterRefreshingDatabase insteadof RefreshDatabase;
    }

    public function test_product_fields_can_be_updated_independently_and_invalid_season_is_rejected(): void
    {
        $admin = $this->createAdmin();
        $product = $this->createProduct(['price' => 500000, 'cost_price' => 280000, 'stock' => 10, 'season' => 'all']);
        $payload = [
            'name' => $product->name,
            'category_id' => $product->category_id,
            'price' => 500000,
            'cost_price' => 280000,
            'stock' => 10,
            'description' => $product->description,
            'is_active' => 1,
        ];

        $this->actingAs($admin)->put(route('admin.products.update', $product), array_merge($payload, ['season' => 'spring']))
            ->assertSessionHasNoErrors();
        $this->assertSame('spring', $product->fresh()->season);
        $this->assertEquals('280000.00', $product->fresh()->cost_price);
        $this->assertDatabaseCount('product_cost_histories', 0);

        $this->put(route('admin.products.update', $product), array_merge($payload, [
            'season' => 'spring',
            'cost_price' => 320000,
            'cost_change_reason' => 'Giá vốn demo mới',
        ]))->assertSessionHasNoErrors();
        $this->assertSame('spring', $product->fresh()->season);
        $this->assertEquals('320000.00', $product->fresh()->cost_price);

        $this->put(route('admin.products.update', $product), array_merge($payload, [
            'season' => 'spring',
            'cost_price' => 320000,
            'stock' => 25,
        ]))->assertSessionHasNoErrors();
        $this->assertSame(25, $product->fresh()->stock);
        $this->assertEquals('500000.00', $product->fresh()->price);

        $this->put(route('admin.products.update', $product), array_merge($payload, ['season' => 'monsoon']))
            ->assertSessionHasErrors('season');
        $this->assertSame('spring', $product->fresh()->season);
    }

    public function test_season_backfill_is_dry_run_safe_and_idempotent(): void
    {
        $product = $this->createProduct(['slug' => 'hoa-tulip', 'season' => 'all']);

        $this->artisan('bloomgift:backfill-product-seasons', ['--dry-run' => true])->assertSuccessful();
        $this->assertSame('all', $product->fresh()->season);

        $this->artisan('bloomgift:backfill-product-seasons')->assertSuccessful();
        $this->assertSame('spring', $product->fresh()->season);

        $this->artisan('bloomgift:backfill-product-seasons')->assertSuccessful();
        $this->assertSame('spring', $product->fresh()->season);
    }
}
