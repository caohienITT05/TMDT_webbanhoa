<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = [
        'order_code',
        'recipient_name',
        'recipient_phone',
        'recipient_address',
        'delivery_date',
        'delivery_slot_id',
        'subtotal',
        'discount',
        'shipping_fee',
        'total',
        'payment_method',
        'payment_status',
        'order_status',
        'note',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function deliverySlot(): BelongsTo
    {
        return $this->belongsTo(DeliverySlot::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }
}
