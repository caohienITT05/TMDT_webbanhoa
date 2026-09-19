<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'sale_price',
        'season', // Thêm trường mùa hoa: spring, summer, autumn, winter, all
        'stock',
        'image',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'stock' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        static::saving(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });
    }

    /**
     * Scope lọc hoa theo mùa vụ
     * Khi lọc mùa (vd: autumn), sẽ lấy hoa mùa đó kết hợp với hoa bốn mùa (all)
     */
    public function scopeSeason(Builder $query, ?string $season): Builder
    {
        if (!empty($season) && in_array($season, ['spring', 'summer', 'autumn', 'winter'])) {
            return $query->where(function ($q) use ($season) {
                $q->where('season', $season)
                    ->orWhere('season', 'all');
            });
        }

        return $query;
    }

    /**
     * Nhãn hiển thị tiếng Việt kèm icon cho từng mùa hoa
     */
    public function getSeasonLabelAttribute(): string
    {
        return match ($this->season) {
            'spring' => 'Hoa Mùa Xuân 🌸',
            'summer' => 'Hoa Mùa Hạ ☀️',
            'autumn' => 'Hoa Mùa Thu 🍂',
            'winter' => 'Hoa Mùa Đông ❄️',
            default => 'Hoa Bốn Mùa 🌿',
        };
    }

    /**
     * Lấy URL ảnh (nếu chưa có ảnh thì hiển thị ảnh mẫu mặc định)
     */
    public function getImageUrlAttribute(): string
    {
        if ($this->image && file_exists(public_path('storage/' . $this->image))) {
            return asset('storage/' . $this->image);
        }

        return 'https://images.unsplash.com/photo-1561181286-d3fee7d55364?w=500&auto=format&fit=crop&q=60';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}