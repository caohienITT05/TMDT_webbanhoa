<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Tests\Concerns\CreatesBloomGiftData;
use Tests\TestCase;

class ChecklistCompletionTest extends TestCase
{
    use CreatesBloomGiftData, RefreshDatabase {
        CreatesBloomGiftData::afterRefreshingDatabase insteadof RefreshDatabase;
    }

    public function test_fix_db_is_not_a_public_route(): void
    {
        $this->get('/fix-db')->assertNotFound();
    }

    public function test_customer_cannot_start_or_complete_paypal_for_someone_elses_order(): void
    {
        $owner = $this->createCustomer();
        $otherCustomer = $this->createCustomer();
        $order = $this->createOrder($owner, ['payment_method' => 'paypal']);
        $this->createPayment($order, ['payment_method' => 'paypal']);

        $this->actingAs($otherCustomer)
            ->get(route('paypal.create', $order))
            ->assertForbidden();

        $this->actingAs($otherCustomer)
            ->get(route('paypal.success', ['order_id' => $order->id, 'token' => 'test-token']))
            ->assertForbidden();
    }

    public function test_customer_category_page_renders_parent_and_child_navigation(): void
    {
        $parent = Category::create(['name' => 'Hoa theo dịp', 'slug' => 'hoa-theo-dip']);
        $child = Category::create(['name' => 'Hoa sinh nhật', 'slug' => 'hoa-sinh-nhat', 'parent_id' => $parent->id]);

        $this->get(route('categories'))
            ->assertOk()
            ->assertSee('Hoa theo dịp')
            ->assertSee('Danh mục con')
            ->assertSee('Hoa sinh nhật')
            ->assertSee(route('categories.show', $child->slug), false);
    }

    public function test_product_list_supports_combined_get_filters_and_sorting(): void
    {
        $category = Category::create(['name' => 'Hoa theo mùa', 'slug' => 'hoa-theo-mua']);
        $match = $this->createProduct([
            'category_id' => $category->id,
            'name' => 'Hoa xuân đang giảm giá',
            'slug' => 'hoa-xuan-dang-giam-gia',
            'price' => 450000,
            'sale_price' => 400000,
            'season' => 'spring',
            'stock' => 5,
        ]);
        $this->createProduct([
            'category_id' => $category->id,
            'name' => 'Hoa xuân đã hết hàng',
            'slug' => 'hoa-xuan-da-het-hang',
            'price' => 390000,
            'sale_price' => null,
            'season' => 'spring',
            'stock' => 0,
        ]);

        $this->get(route('products.index', [
            'search' => 'Hoa xuân',
            'category' => $category->id,
            'min_price' => 350000,
            'max_price' => 450000,
            'season' => 'spring',
            'in_stock' => 1,
            'sale' => 1,
            'sort' => 'price_asc',
        ]))
            ->assertOk()
            ->assertSee($match->name)
            ->assertDontSee('Hoa xuân đã hết hàng')
            ->assertSee('Giá từ')
            ->assertSee('Mùa vụ')
            ->assertSee('Đang khuyến mãi');
    }

    public function test_product_detail_has_meta_description_and_real_product_json_ld(): void
    {
        $product = $this->createProduct([
            'name' => 'Bó hoa kiểm tra SEO',
            'slug' => 'bo-hoa-kiem-tra-seo',
            'description' => 'Mô tả dùng để xác nhận dữ liệu có cấu trúc sản phẩm.',
            'price' => 450000,
            'sale_price' => 400000,
            'stock' => 2,
        ]);

        $this->get(route('product.detail', $product->slug))
            ->assertOk()
            ->assertSee('name="description"', false)
            ->assertSee('application/ld+json', false)
            ->assertSee('"@type":"Product"', false)
            ->assertSee($product->name);
    }

    public function test_sitemap_and_robots_use_the_configured_application_url(): void
    {
        $product = $this->createProduct(['slug' => 'hoa-sitemap']);
        $category = $product->category;
        config()->set('app.url', 'https://bloomgift.example');

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('https://bloomgift.example/san-pham/'.$product->slug, false)
            ->assertSee('https://bloomgift.example/danh-muc/'.$category->slug, false);

        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Sitemap: https://bloomgift.example/sitemap.xml', false);
    }

    public function test_backup_command_checks_mysqldump_without_creating_a_file_in_dry_run(): void
    {
        $originalDefault = config('database.default');
        $originalMysql = config('database.connections.mysql');
        config()->set('database.default', 'mysql');
        config()->set('database.connections.mysql', [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => 'bloomgift_test',
            'username' => 'tester',
            'password' => 'not-used',
        ]);
        Process::fake(['*' => Process::result('mysqldump Ver 8.0')]);

        try {
            $this->artisan('bloomgift:backup-database', ['--dry-run' => true])
                ->expectsOutput('Đã kiểm tra cấu hình MySQL và mysqldump. Dry-run không tạo file backup.')
                ->assertSuccessful();

            Process::assertRan(fn ($process) => str_contains(implode(' ', $process->command), '--version'));
        } finally {
            config()->set('database.default', $originalDefault);
            config()->set('database.connections.mysql', $originalMysql);
        }
    }

    public function test_terms_identifies_demo_seller_information_and_required_transaction_sections(): void
    {
        $this->get(route('policies.terms'))
            ->assertOk()
            ->assertSee('hệ thống demo học tập')
            ->assertSee('Mã số thuế:')
            ->assertSee('Chưa cập nhật')
            ->assertSee('Hủy đơn hàng')
            ->assertSee('Trách nhiệm của các bên');
    }
}
