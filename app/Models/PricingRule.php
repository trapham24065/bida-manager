<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingRule extends Model
{

    use HasFactory;

    protected $fillable
        = [
            'name',
            'start_time',
            'end_time',
            'price_per_hour',
            'is_active',
            'table_type_id',
        ];

    protected $casts
        = [
            'start_time' => 'datetime',
            'end_time'   => 'datetime',
        ];

    public function tableType(): BelongsTo
    {
        return $this->belongsTo(TableType::class);
    }

}
