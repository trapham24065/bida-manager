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

}
