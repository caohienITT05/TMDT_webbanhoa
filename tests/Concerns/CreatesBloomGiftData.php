<?php

namespace Tests\Concerns;

use App\Models\Category;
use App\Models\CustomOrderRequest;
use App\Models\DeliverySlot;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

trait CreatesBloomGiftData
{
    /**
     * The live application currently uses these order columns, while the tracked
     * migration history predates them. Add them only to SQLite :memory: so the
     * feature tests exercise the same order payload without changing MySQL or
     * application migrations.
     */
    protected function afterRefreshingDatabase(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('orders', 'discount_amount')) {
                $table->decimal('discount_amount', 15, 2)->default(0);
            }

            if (! Schema::hasColumn('orders', 'total_amount')) {
                $table->decimal('total_amount', 15, 2)->default(0);
            }

            if (! Schema::hasColumn('orders', 'status')) {
                $table->string('status')->default('PENDING');
            }

            if (! Schema::hasColumn('orders', 'order_note')) {
                $table->text('order_note')->nullable();
            }
        });

        Schema::table('order_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('order_items', 'total')) {
                $table->decimal('total', 15, 2)->default(0);
            }
        });

        // The production database already uses this voucher shape. Keep the
        // in-memory test schema aligned without changing application migrations.
        Schema::table('vouchers', function (Blueprint $table): void {
            if (! Schema::hasColumn('vouchers', 'name')) {
                $table->string('name')->nullable();
            }
            if (! Schema::hasColumn('vouchers', 'type')) {
                $table->string('type')->nullable();
            }
            if (! Schema::hasColumn('vouchers', 'value')) {
                $table->decimal('value', 12, 2)->nullable();
            }
            if (! Schema::hasColumn('vouchers', 'min_order_amount')) {
                $table->decimal('min_order_amount', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('vouchers', 'max_discount')) {
                $table->decimal('max_discount', 12, 2)->nullable();
            }
            if (! Schema::hasColumn('vouchers', 'start_time')) {
                $table->dateTime('start_time')->nullable();
            }
            if (! Schema::hasColumn('vouchers', 'end_time')) {
                $table->dateTime('end_time')->nullable();
            }
            if (! Schema::hasColumn('vouchers', 'used_count')) {
                $table->unsignedInteger('used_count')->default(0);
            }
        });
    }

    protected function createCustomer(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'customer',
            'is_active' => true,
        ], $attributes));
    }

    protected function createAdmin(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'admin',
            'is_active' => true,
        ], $attributes));
    }

    protected function createProduct(array $attributes = []): Product
    {
        $suffix = Str::lower(Str::random(12));
        $category = Category::create([
            'name' => 'Danh mục test ' . $suffix,
            'slug' => 'danh-muc-test-' . $suffix,
        ]);

        return Product::create(array_merge([
            'category_id' => $category->id,
            'name' => 'Hoa test ' . $suffix,
            'slug' => 'hoa-test-' . $suffix,
            'description' => 'Sản phẩm chỉ dùng trong test tự động.',
            'price' => 350000,
            'sale_price' => null,
            'season' => 'all',
            'stock' => 10,
            'image' => null,
            'is_active' => true,
        ], $attributes));
    }

    protected function createDeliverySlot(array $attributes = []): DeliverySlot
    {
        return DeliverySlot::create(array_merge([
            'name' => '08:00 - 10:00',
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'max_orders' => 10,
            'is_active' => true,
        ], $attributes));
    }

    protected function createOrder(?User $customer = null, array $attributes = []): Order
    {
        $slot = $this->createDeliverySlot();

        return Order::create(array_merge([
            'user_id' => $customer?->id,
            'delivery_slot_id' => $slot->id,
            'order_code' => 'BGTEST' . strtoupper(Str::random(10)),
            'recipient_name' => 'Nguyễn Thị Hoa',
            'recipient_phone' => '0901234567',
            'recipient_address' => '123 Đường Hoa, Quận 1, TP.HCM',
            'delivery_date' => now()->addDay()->toDateString(),
            'subtotal' => 350000,
            'discount' => 0,
            'discount_amount' => 0,
            'shipping_fee' => 0,
            'total' => 350000,
            'total_amount' => 350000,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'pending',
            'status' => 'PENDING',
            'note' => null,
        ], $attributes));
    }

    protected function createPayment(Order $order, array $attributes = []): Payment
    {
        return Payment::create(array_merge([
            'order_id' => $order->id,
            'payment_method' => $order->payment_method,
            'transaction_id' => null,
            'amount' => $order->total,
            'status' => 'pending',
            'paid_at' => null,
        ], $attributes));
    }

    protected function createCustomOrderRequest(?User $customer = null, array $attributes = []): CustomOrderRequest
    {
        return CustomOrderRequest::create(array_merge([
            'request_code' => 'YC' . strtoupper(Str::random(10)),
            'user_id' => $customer?->id,
            'customer_name' => 'Nguyễn Thị Hoa',
            'phone' => '0901234567',
            'email' => 'hoa@example.test',
            'occasion' => 'Sinh nhật',
            'flower_type' => 'Hoa hồng',
            'quantity' => 12,
            'budget' => 500000,
            'delivery_date' => now()->addDay()->toDateString(),
            'delivery_address' => '123 Đường Hoa, Quận 1, TP.HCM',
            'message' => 'Xin gói hoa tông màu đỏ.',
            'status' => 'pending',
        ], $attributes));
    }
}
