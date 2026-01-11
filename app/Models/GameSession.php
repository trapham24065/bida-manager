<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameSession extends Model
{

    use HasFactory;

    protected $table = 'game_sessions';

    protected $guarded = [];

    protected $casts
        = [
            'start_time' => 'datetime',
            'end_time'   => 'datetime',
        ];

    public function bidaTable(): BelongsTo
    {
        return $this->belongsTo(Table::class, 'table_id')->withDefault([
            'name' => 'Mang về (Takeaway)',
        ]);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

}
