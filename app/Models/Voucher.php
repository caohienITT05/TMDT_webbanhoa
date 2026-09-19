<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Scope lọc các voucher đang trong khung giờ hiệu lực và còn lượt dùng
     */
    public function scopeValid($query)
    {
        $now = now();
        return $query->where('is_active', true)
            ->where('start_time', '<=', $now)
            ->where('end_time', '>=', $now)
            ->whereColumn('used_count', '<', 'usage_limit');
    }

    /**
     * Tính toán số tiền được giảm dựa trên giá trị đơn hàng
     */
    public function calculateDiscount($orderTotal)
    {
        if ($orderTotal < $this->min_order_amount) {
            return 0;
        }

        if ($this->type === 'percent') {
            $discount = ($orderTotal * $this->value) / 100;
            if ($this->max_discount && $discount > $this->max_discount) {
                $discount = $this->max_discount;
            }
            return $discount;
        }

        return min($this->value, $orderTotal);
    }
}