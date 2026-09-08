<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'customer_name', 'customer_phone', 'customer_address',
        'note', 'delivery_date', 'delivery_time_slot', 'gift_card_message',
        'total_amount', 'payment_method', 'payment_status', 'status'
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
