<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

protected $fillable = [
    'name',
    'slug',
    'parent_id',
    'is_seasonal',
    'description',
    'image',
    'is_active',
];

    protected $casts = [
        'is_seasonal' => 'boolean',
    ];

    // Tự động sinh slug nếu không nhập
    protected static function booted()
    {
        static::saving(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    // Danh mục cha
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // Danh mục con
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    // Quan hệ với sản phẩm
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
