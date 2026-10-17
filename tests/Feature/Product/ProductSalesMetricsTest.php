<?php

namespace Tests\Feature\Product;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesBloomGiftData;
use Tests\TestCase;

class ProductSalesMetricsTest extends TestCase
{
    use CreatesBloomGiftData, RefreshDatabase {
        CreatesBloomGiftData::afterRefreshingDatabase insteadof RefreshDatabase;
    }

    public function test_product_sales_separate_real_orders_from_completed_sales_and_exclude_cancellations(): void
    {
        $product = $this->createProduct();
        $pending = $this->createOrder(null, ['order_status' => 'pending', 'status' => 'PENDING']);
        $inconsistent = $this->createOrder(null, ['order_status' => 'processing', 'status' => 'COMPLETED']);
        $completed = $this->createOrder(null, ['order_status' => 'completed', 'status' => 'COMPLETED']);
        $cancelled = $this->createOrder(null, ['order_status' => 'cancelled', 'status' => 'CANCELLED']);
        $returned = $this->createOrder(null, ['order_status' => 'returned', 'status' => 'RETURNED']);
        $refunded = $this->createOrder(null, ['order_status' => 'completed', 'status' => 'COMPLETED', 'payment_status' => 'refunded']);
        $failedPayment = $this->createOrder(null, ['order_status' => 'failed', 'status' => 'FAILED', 'payment_status' => 'failed', 'payment_method' => 'paypal']);

        foreach ([[$pending, 3], [$inconsistent, 8], [$completed, 2], [$completed, 1], [$cancelled, 7], [$returned, 4], [$refunded, 9], [$failedPayment, 6]] as [$order, $quantity]) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'price' => 350000,
                'quantity' => $quantity,
                'subtotal' => 350000 * $quantity,
            ]);
        }

        $metrics = Product::withSalesMetrics()->findOrFail($product->id);
        $this->assertSame(3, (int) $metrics->sold_quantity);
        $this->assertSame(1, (int) $metrics->sold_order_count);
        $this->assertSame(14, (int) $metrics->ordered_quantity);
        $this->assertSame(3, (int) $metrics->ordered_order_count);
        $this->assertSame(14, (int) $metrics->display_sold_quantity);
        $this->assertSame(3, (int) $metrics->display_sold_order_count);

        $this->get(route('products.index', ['sort' => 'bestselling']))
            ->assertOk()
            ->assertSee('Đã bán 14')
            ->assertDontSee('đơn hợp lệ')
            ->assertDontSee('Đã đặt:');

        $pending->update(['order_status' => 'completed', 'status' => 'COMPLETED']);
        $this->assertSame(14, (int) Product::withSalesMetrics()->findOrFail($product->id)->display_sold_quantity);

        $pending->update(['order_status' => 'cancelled', 'status' => 'CANCELLED']);
        $this->assertSame(11, (int) Product::withSalesMetrics()->findOrFail($product->id)->display_sold_quantity);
    }

    public function test_product_sales_scope_can_compare_the_same_local_date_range(): void
    {
        $product = $this->createProduct();
        $inside = $this->createOrder(null, ['order_status' => 'processing', 'status' => 'PROCESSING', 'created_at' => '2026-10-10 12:00:00']);
        $outside = $this->createOrder(null, ['order_status' => 'completed', 'status' => 'COMPLETED', 'created_at' => '2026-09-30 12:00:00']);

        foreach ([[$inside, 4], [$outside, 9]] as [$order, $quantity]) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'price' => 350000,
                'quantity' => $quantity,
                'subtotal' => 350000 * $quantity,
            ]);
        }

        $metrics = Product::withSalesMetrics('2026-10-01', '2026-10-31')->findOrFail($product->id);

        $this->assertSame(4, (int) $metrics->display_sold_quantity);
        $this->assertSame(0, (int) $metrics->sold_quantity);
    }

    public function test_catalog_admin_dashboard_and_finance_match_product_quantity_for_all_time(): void
    {
        $product = $this->createProduct(['name' => 'Shared all-time bestseller']);
        $processing = $this->createOrder(null, ['order_status' => 'processing', 'status' => 'PROCESSING']);
        $completed = $this->createOrder(null, ['order_status' => 'completed', 'status' => 'COMPLETED']);
        $cancelled = $this->createOrder(null, ['order_status' => 'cancelled', 'status' => 'CANCELLED']);

        foreach ([[$processing, 3], [$completed, 2], [$cancelled, 7]] as [$order, $quantity]) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name' => 'Historical snapshot name',
                'price' => 350000,
                'quantity' => $quantity,
                'subtotal' => 350000 * $quantity,
            ]);
        }

        $this->get(route('products.index', ['sort' => 'bestselling']))
            ->assertOk()->assertSee('Shared all-time bestseller')->assertSee('Đã bán 5');

        $this->actingAs($this->createAdmin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Sản phẩm bán chạy toàn thời gian')
            ->assertViewHas('bestSellingProducts', fn ($rows) => $rows->contains(fn ($row) => $row->id === $product->id && (int) $row->display_sold_quantity === 5));

        $this->get(route('admin.finance.index', ['period' => 'all']))
            ->assertOk()
            ->assertViewHas('bestSellers', fn ($rows) => $rows->contains(fn ($row) => (int) $row->product_id === $product->id && (int) $row->quantity === 5 && $row->product_name === $product->name));
    }

    public function test_combined_catalog_filters_use_sale_price_and_keep_filters_when_paginating(): void
    {
        $matchingProduct = $this->createProduct([
            'name' => 'BloomGift QA Bestseller',
            'slug' => 'bloomgift-qa-bestseller',
            'price' => 590000,
            'sale_price' => 300000,
            'season' => 'spring',
            'stock' => 4,
        ]);
        $categoryId = $matchingProduct->category_id;

        ProductReview::create([
            'product_id' => $matchingProduct->id,
            'reviewer_name' => 'QA Customer',
            'reviewer_email' => 'qa-filter@example.test',
            'rating' => 5,
            'content' => 'Sản phẩm dùng để kiểm tra kết hợp bộ lọc.',
            'status' => 'published',
            'published_at' => now(),
            'is_verified_purchase' => true,
        ]);

        $order = $this->createOrder(null, ['order_status' => 'pending', 'status' => 'PENDING']);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $matchingProduct->id,
            'product_name' => $matchingProduct->name,
            'price' => 300000,
            'quantity' => 20,
            'subtotal' => 6000000,
        ]);

        for ($index = 1; $index < 13; $index++) {
            $product = $this->createProduct([
                'category_id' => $categoryId,
                'name' => sprintf('BloomGift QA %02d', $index),
                'slug' => sprintf('bloomgift-qa-%02d', $index),
                'price' => 450000,
                'sale_price' => 300000,
                'season' => 'spring',
                'stock' => 2,
            ]);
            ProductReview::create([
                'product_id' => $product->id,
                'reviewer_name' => 'QA Customer ' . $index,
                'reviewer_email' => 'qa-filter-' . $index . '@example.test',
                'rating' => 4,
                'content' => 'Đánh giá dùng để kiểm tra bộ lọc.',
                'status' => 'published',
                'published_at' => now(),
                'is_verified_purchase' => true,
            ]);
        }

        $excludedCases = [
            ['name' => 'BloomGift QA Wrong Price', 'price' => 190000, 'sale_price' => 150000, 'season' => 'spring', 'stock' => 3, 'category_id' => $categoryId],
            ['name' => 'BloomGift QA Wrong Season', 'price' => 450000, 'sale_price' => 300000, 'season' => 'summer', 'stock' => 3, 'category_id' => $categoryId],
            ['name' => 'BloomGift QA Out Of Stock', 'price' => 450000, 'sale_price' => 300000, 'season' => 'spring', 'stock' => 0, 'category_id' => $categoryId],
            ['name' => 'BloomGift QA No Sale', 'price' => 450000, 'sale_price' => null, 'season' => 'spring', 'stock' => 3, 'category_id' => $categoryId],
            ['name' => 'BloomGift QA Low Rating', 'price' => 450000, 'sale_price' => 300000, 'season' => 'spring', 'stock' => 3, 'category_id' => $categoryId],
            ['name' => 'BloomGift QA Outside Category', 'price' => 450000, 'sale_price' => 300000, 'season' => 'spring', 'stock' => 3],
        ];

        foreach ($excludedCases as $index => $attributes) {
            $product = $this->createProduct(array_merge($attributes, [
                'slug' => 'bloomgift-qa-excluded-' . $index,
            ]));
            if ($attributes['name'] === 'BloomGift QA Low Rating') {
                ProductReview::create([
                    'product_id' => $product->id,
                    'reviewer_name' => 'QA Low Rating',
                    'reviewer_email' => 'qa-low-rating@example.test',
                    'rating' => 3,
                    'content' => 'Đánh giá thấp hơn điều kiện.',
                    'status' => 'published',
                    'published_at' => now(),
                    'is_verified_purchase' => false,
                ]);
            }
        }

        $filters = [
            'search' => 'BloomGift QA',
            'category' => $categoryId,
            'min_price' => 200000,
            'max_price' => 500000,
            'season' => 'spring',
            'min_rating' => 4,
            'in_stock' => 1,
            'sale' => 1,
            'sort' => 'bestselling',
        ];

        $response = $this->get(route('products.index', $filters))->assertOk();
        $this->assertSame(13, $response->getOriginalContent()->getData()['products']->total());
        $response->assertSee($matchingProduct->name);
        $response->assertDontSee('Wrong Price');
        $response->assertDontSee('Wrong Season');
        $response->assertDontSee('Out Of Stock');
        $response->assertDontSee('No Sale');
        $response->assertDontSee('Low Rating');
        $response->assertDontSee('Outside Category');
        foreach ([
            'search=BloomGift', 'category=' . $categoryId, 'min_price=200000', 'max_price=500000',
            'season=spring', 'min_rating=4', 'in_stock=1', 'sale=1', 'sort=bestselling',
        ] as $queryParameter) {
            $response->assertSee($queryParameter, false);
        }

        $this->get(route('products.index', array_merge($filters, ['page' => 2])))
            ->assertOk()
            ->assertSee('search=BloomGift', false)
            ->assertSee('min_price=200000', false)
            ->assertSee('min_rating=4', false);

        $response->assertSee('Xóa lọc');
        $response->assertSee('href="' . route('products.index') . '"', false);
    }
}
