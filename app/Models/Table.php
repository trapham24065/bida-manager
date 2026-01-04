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

    public function tableType(): BelongsTo
    {
        return $this->belongsTo(TableType::class);
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

}
