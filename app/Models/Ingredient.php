<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Ingredient extends Model
{

    protected $fillable = [
        'name',
        'unit',
        'stock',
        'cost_per_unit',
        'min_stock',
        'is_active',
    ];

    protected $casts = [
        'stock' => 'decimal:2',
        'cost_per_unit' => 'decimal:0',
        'min_stock' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Các sản phẩm sử dụng nguyên liệu này
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_ingredients')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    /**
     * Kiểm tra nguyên liệu có đủ không
     */
    public function hasEnoughStock(float $needed): bool
    {
        return $this->stock >= $needed;
    }

    /**
     * Kiểm tra nguyên liệu sắp hết (dưới mức tối thiểu)
     */
    public function isLowStock(): bool
    {
        return $this->stock <= $this->min_stock;
    }

    /**
     * Trừ tồn kho
     */
    public function decrementStock(float $amount): void
    {
        $this->decrement('stock', $amount);
    }

    /**
     * Cộng tồn kho (khi trả hàng hoặc nhập kho)
     */
    public function incrementStock(float $amount): void
    {
        $this->increment('stock', $amount);
    }
}
