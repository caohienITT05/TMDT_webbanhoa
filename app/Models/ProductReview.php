<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductReview extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_verified_purchase' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function negativeEvents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductReviewNegativeEvent::class, 'product_review_id');
    }

    public function reports(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductReviewReport::class, 'product_review_id');
    }
}
