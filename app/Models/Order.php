<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'delivery_slot_id',
        'gift_card_id',
        'gift_wrap_id',
        'voucher_id',
        'recipient_name',
        'recipient_phone',
        'recipient_address',
        'delivery_date',
        'card_message',
        'order_note',
        'subtotal',
        'discount_amount',
        'total_amount',
        'status',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function deliverySlot(): BelongsTo
    {
        return $this->belongsTo(DeliverySlot::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }
}