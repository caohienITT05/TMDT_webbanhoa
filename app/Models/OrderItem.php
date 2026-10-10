<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'cost_price_snapshot' => 'decimal:2',
        'price'    => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total'    => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (OrderItem $item): void {
            if ($item->cost_price_snapshot === null && $item->product_id) {
                $item->cost_price_snapshot = Product::whereKey($item->product_id)->value('cost_price');
                if ($item->cost_price_snapshot !== null) {
                    $item->cost_price_snapshot_source = 'order_created';
                }
            }
        });
    }

    /**
     * Thuộc về đơn hàng nào
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Sản phẩm hoa tương ứng
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
