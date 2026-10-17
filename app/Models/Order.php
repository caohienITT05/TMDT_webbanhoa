<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class Order extends Model
{
    use HasFactory;

    public const SALES_INVALID_STATUSES = ['cancelled', 'canceled', 'returned', 'refunded', 'failed', 'payment_failed', 'declined', 'expired', 'voided'];

    protected $guarded = [];

    protected $casts = [
        'delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'gift_card_fee' => 'decimal:2',
        'gift_wrap_fee' => 'decimal:2',
        'total' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'guest_access_token_expires_at' => 'datetime',
        'inventory_reserved_at' => 'datetime',
        'inventory_released_at' => 'datetime',
    ];

    public function getCreatedAtLocalAttribute(): ?Carbon
    {
        return $this->created_at?->copy()->setTimezone(config('app.display_timezone', 'Asia/Ho_Chi_Minh'));
    }

    public function scopeFinanciallyValid(Builder $query): Builder
    {
        foreach (['order_status', 'status', 'payment_status'] as $field) {
            if (Schema::hasColumn('orders', $field)) {
                $statuses = "'" . implode("','", self::SALES_INVALID_STATUSES) . "'";
                $query->whereRaw("LOWER(COALESCE({$field}, '')) NOT IN ({$statuses})");
            }
        }

        return $query;
    }

    public function scopeCompletedOrDelivered(Builder $query): Builder
    {
        $statusColumns = [];
        if (Schema::hasColumn('orders', 'order_status')) {
            $statusColumns[] = "NULLIF(order_status, '')";
        }
        if (Schema::hasColumn('orders', 'status')) {
            $statusColumns[] = 'status';
        }
        $effectiveStatus = 'LOWER(COALESCE(' . implode(', ', $statusColumns ?: ['NULL']) . ", ''))";

        return $query->financiallyValid()->whereRaw(
            "{$effectiveStatus} IN ('completed', 'delivered')"
        );
    }

    /**
     * Khách hàng đặt hoa
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Khung giờ giao hàng
     */
    public function deliverySlot(): BelongsTo
    {
        return $this->belongsTo(DeliverySlot::class);
    }

    /**
     * Danh sách sản phẩm hoa trong đơn hàng
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Bí danh tương thích cho phần Admin
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Thông tin thanh toán (PayPal / COD)
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function customOrderRequest(): HasOne
    {
        return $this->hasOne(CustomOrderRequest::class);
    }
    // Tự động trả về order_code nếu giao diện gọi order_number
    public function getOrderNumberAttribute()
    {
        return $this->attributes['order_code'] ?? ('#' . $this->id);
    }
}
