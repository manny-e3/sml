<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MarketCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the product types for the market category
     */
    public function productTypes()
    {
        return $this->hasMany(ProductType::class);
    }

    /**
     * Scope to get only active market categories
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
