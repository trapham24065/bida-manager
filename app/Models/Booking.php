<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{

    protected $fillable
        = [
            'table_id',
            'customer_name',
            'phone',
            'booking_time',
            'duration_minutes',
            'status',
            'note',
            'is_reminded_upcoming',
            'is_reminded_late',
        ];

    protected $casts
        = [
            'booking_time'     => 'datetime', // Ép kiểu thành đối tượng thời gian
            'duration_minutes' => 'integer',
        ];

    public function bidaTable(): BelongsTo
    {
        return $this->belongsTo(Table::class, 'table_id');
    }

}
