<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Table extends Model
{

    use LogsActivity;

    protected $guarded = [];

    // Hoặc thêm vào fillable nếu dùng fillable thay vì guarded
    protected $fillable
        = [
            'name',
            'table_type_id',
            'price_per_hour',
            'is_active',
            'position_x',
            'position_y',  // Thêm 2 cột này
        ];

    public function tableType(): BelongsTo
    {
        return $this->belongsTo(TableType::class, 'table_type_id');
    }

    // Một bàn có nhiều phiên chơi
    public function gameSessions(): HasMany
    {
        return $this->hasMany(GameSession::class);
    }

    // Phiên đang chơi
    public function currentSession(): HasOne
    {
        return $this->hasOne(GameSession::class)
            ->where('status', 'running');
    }

    // Kiểm tra có phiên đang chơi không
    public function hasRunningSession(): bool
    {
        return $this->currentSession()->exists();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

}
