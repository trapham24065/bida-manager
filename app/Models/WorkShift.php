<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkShift extends Model
{

    use HasFactory;

    protected $fillable
        = [
            'user_id',
            'start_time',
            'end_time',
            'initial_cash',
            'total_cash_money',
            'total_transfer_money',
            'reported_cash',
            'difference',
            'note',
            'status',
        ];

    // Tìm ca đang mở của user hiện tại
    public static function myCurrentShift()
    {
        return self::where('user_id', auth()->id())
            ->where('status', 'open')
            ->first();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gameSessions(): HasMany
    {
        return $this->hasMany(GameSession::class);
    }

}
