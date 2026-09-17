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
        'price'    => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total'    => 'decimal:2',
    ];

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