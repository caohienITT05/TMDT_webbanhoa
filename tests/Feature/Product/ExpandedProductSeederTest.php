<?php

namespace Tests\Feature\Product;

use App\Models\Category;
use App\Models\Product;
use Database\Seeders\BloomGift36ProductsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesBloomGiftData;
use Tests\TestCase;

class ExpandedProductSeederTest extends TestCase
{
    use CreatesBloomGiftData, RefreshDatabase {
        CreatesBloomGiftData::afterRefreshingDatabase insteadof RefreshDatabase;
    }

    public function test_catalog_has_36_unique_local_webp_images(): void
    {
        $catalog = json_decode(file_get_contents(database_path('data/bloomgift-expanded-products.json')), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(36, $catalog);
        $this->assertCount(36, array_unique(array_column($catalog, 'slug')));

        foreach ($catalog as $row) {
            $path = public_path('images/expanded/'.$row['slug'].'.webp');
            $this->assertFileExists($path);
            $image = getimagesize($path);
            $this->assertSame('image/webp', $image['mime']);
            $this->assertSame([800, 800], [$image[0], $image[1]]);
            $this->assertTrue($row['cost_confirmed']);
            $this->assertGreaterThan(0, $row['cost_price']);
            $this->assertGreaterThan($row['cost_price'], $row['sale_price']);
            $this->assertGreaterThan($row['sale_price'], $row['price']);
            $this->assertGreaterThan(0, $row['stock']);
            $this->assertContains($row['season'], Product::SEASONS);
        }
    }

    public function test_seeder_inserts_36_products_once_with_demo_finance_and_inventory_data(): void
    {
        $count = Product::count();
        (new BloomGift36ProductsSeeder)->run();
        $this->assertSame($count + 36, Product::count());
        $this->assertSame(36, Product::where('image', 'like', 'products/expanded/%')->count());
        $this->assertSame(0, Product::where('image', 'like', 'products/expanded/%')->whereNull('cost_price')->count());
        $this->assertSame(0, Product::where('image', 'like', 'products/expanded/%')->whereNull('sale_price')->count());
        $this->assertSame(0, Product::where('image', 'like', 'products/expanded/%')->where('stock', '<=', 0)->count());
        foreach (['spring', 'summer', 'autumn', 'winter'] as $season) {
            $this->assertGreaterThan(0, Product::where('image', 'like', 'products/expanded/%')->where('season', $season)->count());
        }

        (new BloomGift36ProductsSeeder)->run();
        $this->assertSame($count + 36, Product::count());
    }

    public function test_admin_must_provide_actual_cost_when_creating_a_product(): void
    {
        $admin = $this->createAdmin();
        $category = Category::create(['name' => 'Danh mục giá vốn', 'slug' => 'danh-muc-gia-von']);
        $payload = [
            'name' => 'Sản phẩm mới có giá vốn',
            'category_id' => $category->id,
            'price' => 500000,
            'season' => 'all',
            'stock' => 5,
            'description' => 'Kiểm tra giá vốn.',
            'is_active' => '1',
        ];
        $this->actingAs($admin)->post(route('admin.products.store'), $payload)->assertSessionHasErrors('cost_price');
        $this->post(route('admin.products.store'), $payload + ['cost_price' => 300000])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('products', ['name' => $payload['name'], 'cost_price' => 300000]);
    }
}
