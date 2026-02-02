<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\StockInput;

class Product extends Model
{

    use LogsActivity;
    use HasFactory;

    protected $fillable
    = [
        'name',
        'price',
        'cost_price',
        'stock',
        'image',
        'is_active',
        'is_combo',
        'is_recipe',
        'tax_rate',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_combo' => 'boolean',
        'is_recipe' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll() // Theo dõi TẤT CẢ các cột (tên, giá, tồn kho...)
            ->logOnlyDirty() // Chỉ lưu những cột có thay đổi (cho nhẹ DB)
            ->dontSubmitEmptyLogs(); // Không lưu nếu không có gì thay đổi
    }

    public function StockHistoriesRelationManager(): HasMany
    {
        return $this->hasMany(StockHistory::class);
    }

    public function stockInputs(): HasMany
    {
        return $this->hasMany(StockInput::class)->latest(); // latest() để cái mới nhất lên đầu
    }

    public function comboItems(): BelongsToMany
    {
        return $this->belongsToMany(
            __CLASS__,         // Model liên kết (Chính là Product)
            'product_combos',       // Tên bảng trung gian
            'product_id',           // Khóa ngoại của Combo (Cha)
            'related_product_id'    // Khóa ngoại của Món con
        )->withPivot(['quantity']); // <--- Bắt buộc phải có để lưu số lượng
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function ingredients()
    {
        return $this->belongsToMany(Ingredient::class, 'product_ingredients')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    /**
     * Tính tồn kho thực tế (ảo) cho sản phẩm pha chế
     * = MIN(tồn nguyên liệu / lượng cần) cho tất cả nguyên liệu
     */
    public function getAvailableStock(): int
    {
        // Sản phẩm thường: trả về stock trực tiếp
        if (!$this->is_recipe) {
            return (int) $this->stock;
        }

        // Sản phẩm pha chế: tính từ nguyên liệu
        return $this->calculateRecipeStock();
    }

    /**
     * Tính số lượng có thể pha chế từ nguyên liệu hiện có
     */
    public function calculateRecipeStock(): int
    {
        $ingredients = $this->ingredients;

        // Không có công thức -> không thể pha
        if ($ingredients->isEmpty()) {
            return 0;
        }

        $availablePortions = [];

        foreach ($ingredients as $ingredient) {
            $needed = $ingredient->pivot->quantity; // Lượng cần cho 1 phần

            if ($needed <= 0) {
                continue;
            }

            // Số phần có thể pha từ nguyên liệu này
            $portions = floor($ingredient->stock / $needed);
            $availablePortions[] = $portions;
        }

        // Trả về MIN (nguyên liệu ít nhất quyết định số lượng có thể pha)
        return empty($availablePortions) ? 0 : (int) min($availablePortions);
    }

    /**
     * Kiểm tra có đủ nguyên liệu để pha chế không
     */
    public function canMakeRecipe(int $quantity = 1): bool
    {
        if (!$this->is_recipe) {
            return $this->stock >= $quantity;
        }

        return $this->calculateRecipeStock() >= $quantity;
    }

    /**
     * Lấy danh sách nguyên liệu thiếu (nếu có)
     */
    public function getMissingIngredients(int $quantity = 1): array
    {
        if (!$this->is_recipe) {
            return [];
        }

        $missing = [];
        foreach ($this->ingredients as $ingredient) {
            $needed = $ingredient->pivot->quantity * $quantity;
            if ($ingredient->stock < $needed) {
                $missing[] = [
                    'ingredient' => $ingredient,
                    'needed' => $needed,
                    'available' => $ingredient->stock,
                    'shortage' => $needed - $ingredient->stock,
                ];
            }
        }

        return $missing;
    }
}
