<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomOrderRequest extends Model
{
    protected $guarded = [];

    protected $casts = [
        'delivery_date' => 'date',
        'budget' => 'decimal:2',
        'admin_viewed_at' => 'datetime',
        'contacted_at' => 'datetime',
        'responded_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
