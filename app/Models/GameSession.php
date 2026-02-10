<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class GameSession extends Model
{

    use HasFactory;

    protected $table = 'game_sessions';

    protected $guarded = [];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time'   => 'datetime',
        'paused_at'  => 'datetime',
        'scheduled_until' => 'datetime',
    ];

    public function bidaTable(): BelongsTo
    {
        return $this->belongsTo(Table::class, 'table_id')->withDefault([
            'name' => 'Mang về (Takeaway)',
        ]);
    }

    // Alias cho bidaTable (dùng trong BillingService)
    public function table(): BelongsTo
    {
        return $this->bidaTable();
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relationship: Phiên được ghép vào phiên này
     */
    public function mergedSessions()
    {
        return $this->hasMany(GameSession::class, 'merged_into_session_id');
    }

    /**
     * Relationship: Phiên mà phiên này được ghép vào
     */
    public function mergedIntoSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class, 'merged_into_session_id');
    }

    /**
     * Relationship: Phiên được chuyển từ phiên này
     */
    public function transferredSession()
    {
        return $this->hasOne(GameSession::class, 'transferred_from_session_id');
    }

    /**
     * Relationship: Phiên chuyển từ trước đó
     */
    public function transferredFromSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class, 'transferred_from_session_id');
    }

    /**
     * Kiểm tra bàn có đang tạm dừng không
     */
    public function isPaused(): bool
    {
        return $this->paused_at !== null;
    }

    /**
     * Tạm dừng bàn
     */
    public function pause(): void
    {
        if (!$this->isPaused()) {
            $this->update(['paused_at' => now()]);
        }
    }

    /**
     * Tiếp tục bàn (resume)
     */
    public function resume(): void
    {
        if ($this->isPaused()) {
            // Tính thời gian đã tạm dừng và cộng vào tổng
            $pausedSeconds = Carbon::parse($this->paused_at)->diffInSeconds(now());
            $this->update([
                'paused_at' => null,
                'total_paused_seconds' => $this->total_paused_seconds + $pausedSeconds,
            ]);
        }
    }

    /**
     * Lấy tổng thời gian tạm dừng (bao gồm cả lần đang dừng nếu có)
     */
    public function getTotalPausedSeconds(): int
    {
        $total = $this->total_paused_seconds ?? 0;

        // Nếu đang tạm dừng, cộng thêm thời gian từ lúc pause đến giờ
        if ($this->isPaused()) {
            $total += Carbon::parse($this->paused_at)->diffInSeconds(now());
        }

        return $total;
    }

    /**
     * Schedule an automatic pause after given minutes from now (or from start_time if desired).
     * @param int $minutes
     * @param bool $fromStart If true, schedule from `start_time`, otherwise from now().
     */
    public function schedule(int $minutes, bool $fromStart = false): void
    {
        $base = $fromStart && $this->start_time ? \Illuminate\Support\Carbon::parse($this->start_time) : now();
        $this->update([
            'scheduled_minutes'   => $minutes,
            'scheduled_until'     => $base->copy()->addMinutes($minutes),
            'scheduled_auto_pause' => true,
        ]);
    }

    public function isScheduled(): bool
    {
        return $this->scheduled_auto_pause && $this->scheduled_until !== null;
    }

    public function clearSchedule(): void
    {
        $this->update([
            'scheduled_minutes'   => null,
            'scheduled_until'     => null,
            'scheduled_auto_pause' => false,
        ]);
    }

    /**
     * Lấy thời gian chơi thực tế (đã trừ thời gian tạm dừng)
     */
    public function getActualPlayingMinutes(): int
    {
        $end = $this->end_time ?? now();
        $totalSeconds = Carbon::parse($this->start_time)->diffInSeconds($end);
        $actualSeconds = $totalSeconds - $this->getTotalPausedSeconds();

        return max(1, (int) ceil($actualSeconds / 60));
    }

    /**
     * Kiểm tra phiên có bị ghép vào phiên khác không
     */
    public function isMerged(): bool
    {
        return $this->merged_into_session_id !== null;
    }

    /**
     * Kiểm tra phiên có bị chuyển bàn không
     */
    public function isTransferred(): bool
    {
        return $this->transferred_from_session_id !== null;
    }

    /**
     * Lấy tất cả phiên được ghép vào phiên này (bao gồm cả đệ quy)
     */
    public function getAllMergedSessions()
    {
        $merged = collect([$this]);
        
        foreach ($this->mergedSessions as $session) {
            $merged = $merged->merge($session->getAllMergedSessions());
        }
        
        return $merged;
    }

    /**
     * Tính tổng tiền từ tất cả các phiên được ghép
     */
    public function getTotalMoneyIncludingMerged(): float
    {
        $total = $this->total_money ?? 0;
        
        foreach ($this->mergedSessions as $session) {
            $total += $session->getTotalMoneyIncludingMerged();
        }
        
        return $total;
    }

    /**
     * Lấy tất cả món ăn từ phiên này và các phiên ghép vào
     */
    public function getAllOrderItems()
    {
        $items = $this->orderItems()->get();
        
        foreach ($this->mergedSessions as $session) {
            $items = $items->merge($session->getAllOrderItems());
        }
        
        return $items;
    }
}
