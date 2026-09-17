<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'fee',
        'description',
        'is_active',
    ];

    protected $casts = [
        'fee' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}