<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'payment_method',
        'transaction_id',
        'amount',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function getPaidAtLocalAttribute(): ?Carbon
    {
        return $this->paid_at?->copy()->setTimezone(config('app.display_timezone', 'Asia/Ho_Chi_Minh'));
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
