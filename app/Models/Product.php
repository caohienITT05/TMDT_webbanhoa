<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use App\Support\FinanceDateRange;

class Product extends Model
{
    use HasFactory;

    public const SEASONS = ['spring', 'summer', 'autumn', 'winter', 'all'];

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'cost_price',
        'sale_price',
        'season',
        'stock',
        'image',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
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
        if ($season === 'all') {
            return $query->where('season', 'all');
        }

        if (! empty($season) && in_array($season, ['spring', 'summer', 'autumn', 'winter'], true)) {
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
            default => 'Quanh năm 🌿',
        };
    }

    /** Return this product's public image URL, or null when its file is unavailable. */
    public function getImageUrlAttribute(): ?string
    {
        if ($this->image && Storage::disk('public')->exists($this->image)) {
            return asset('storage/' . $this->image);
        }

        return null;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** Add sales totals sourced only from this product's real order-item rows. */
    public function scopeWithSalesMetrics(Builder $query, ?string $from = null, ?string $to = null): Builder
    {
        $hasLegacyStatus = Schema::hasColumn('orders', 'status');
        $hasPaymentStatus = Schema::hasColumn('orders', 'payment_status');
        $effectiveStatus = "LOWER(COALESCE(NULLIF(sales_orders.order_status, ''), " . ($hasLegacyStatus ? 'sales_orders.status' : 'NULL') . ", ''))";
        $invalidStatuses = "'" . implode("','", Order::SALES_INVALID_STATUSES) . "'";
        $validOrders = DB::table('order_items as sales_items')
            ->join('orders as sales_orders', 'sales_orders.id', '=', 'sales_items.order_id')
            ->whereColumn('sales_items.product_id', 'products.id')
            ->whereRaw("LOWER(COALESCE(sales_orders.order_status, '')) NOT IN ({$invalidStatuses})");
        if ($hasLegacyStatus) {
            $validOrders->whereRaw("LOWER(COALESCE(sales_orders.status, '')) NOT IN ({$invalidStatuses})");
        }
        if ($hasPaymentStatus) {
            $validOrders->whereRaw("LOWER(COALESCE(sales_orders.payment_status, '')) NOT IN ({$invalidStatuses})");
        }
        if ($from !== null && $to !== null) {
            [$start, $endExclusive] = FinanceDateRange::utcBounds($from, $to);
            $validOrders->where('sales_orders.created_at', '>=', $start)->where('sales_orders.created_at', '<', $endExclusive);
        }
        $completedOrders = (clone $validOrders)->whereRaw("{$effectiveStatus} IN ('completed','delivered')");

        return $query->addSelect([
            'sold_quantity' => (clone $completedOrders)->selectRaw('COALESCE(SUM(sales_items.quantity), 0)'),
            'sold_order_count' => (clone $completedOrders)->selectRaw('COUNT(DISTINCT sales_items.order_id)'),
            'ordered_quantity' => (clone $validOrders)->selectRaw('COALESCE(SUM(sales_items.quantity), 0)'),
            'ordered_order_count' => (clone $validOrders)->selectRaw('COUNT(DISTINCT sales_items.order_id)'),
            // Storefront demand counts all active placed orders, separate from recognized revenue.
            'display_sold_quantity' => (clone $validOrders)->selectRaw('COALESCE(SUM(sales_items.quantity), 0)'),
            'display_sold_order_count' => (clone $validOrders)->selectRaw('COUNT(DISTINCT sales_items.order_id)'),
        ]);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function publishedReviews(): HasMany
    {
        return $this->reviews()->where('status', 'published')->whereNotNull('published_at');
    }

    public function costHistories(): HasMany
    {
        return $this->hasMany(ProductCostHistory::class)->latest('effective_at');
    }
}
