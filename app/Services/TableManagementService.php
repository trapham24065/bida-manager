<?php

namespace App\Services;

use App\Models\GameSession;
use Illuminate\Support\Carbon;

class TableManagementService
{
    /**
     * Ghép hóa đơn từ phiên này vào phiên khác
     *
     * @param GameSession $sourceSession Phiên nguồn (sẽ bị ghép vào)
     * @param GameSession $targetSession Phiên đích (sẽ nhận hết dữ liệu)
     * @return bool
     */
    public function mergeSession(GameSession $sourceSession, GameSession $targetSession): bool
    {
        // Không thể ghép phiên đã hoàn thành
        if ($sourceSession->status !== 'running' || $targetSession->status !== 'running') {
            throw new \RuntimeException('Chỉ có thể ghép các phiên đang chạy');
        }

        // Không thể ghép một phiên vào chính nó
        if ($sourceSession->id === $targetSession->id) {
            throw new \RuntimeException('Không thể ghép phiên vào chính nó');
        }

        // **Tính thời gian chơi thực tế của cả 2 phiên**
        $sourceActualSeconds = Carbon::parse($sourceSession->start_time)->diffInSeconds(now()) 
            - $sourceSession->getTotalPausedSeconds();
        $targetActualSeconds = Carbon::parse($targetSession->start_time)->diffInSeconds(now()) 
            - $targetSession->getTotalPausedSeconds();
        
        // **Tổng thời gian ghép = thời gian phiên A + thời gian phiên B**
        $totalMergedSeconds = $sourceActualSeconds + $targetActualSeconds;
        
        // **Adjust start_time của phiên đích để tính tổng thời gian**
        // Công thức: new_start_time = now - total_merged_seconds
        $newStartTime = now()->subSeconds($totalMergedSeconds);

        // Cập nhật tất cả order items từ phiên nguồn sang phiên đích
        $sourceSession->orderItems()->update([
            'game_session_id' => $targetSession->id,
        ]);

        // Cập nhật phiên đích: điều chỉnh start_time và giữ pause time
        $targetSession->update([
            'start_time' => $newStartTime,
            // Không reset pause vì nó đã được tính trong getTotalPausedSeconds()
        ]);

        // Đánh dấu phiên nguồn đã bị ghép vào phiên đích
        $sourceSession->update([
            'merged_into_session_id' => $targetSession->id,
            'status' => 'merged',
        ]);

        return true;
    }

    /**
     * Đổi bàn: Tạo phiên mới cho bàn mới, chuyển dữ liệu từ phiên cũ
     *
     * @param GameSession $oldSession Phiên trên bàn cũ
     * @param int $newTableId ID của bàn mới
     * @param string $reason Lý do đổi bàn
     * @return GameSession Phiên mới
     */
    public function transferTableSession(GameSession $oldSession, int $newTableId, string $reason = ''): GameSession
    {
        // Không thể đổi phiên đã hoàn thành
        if ($oldSession->status !== 'running') {
            throw new \RuntimeException('Chỉ có thể đổi bàn cho phiên đang chạy');
        }

        // Tạo phiên mới trên bàn mới
        // **Quan trọng**: Giữ lại start_time từ phiên cũ để tính tiền giờ liên tục
        $newSession = GameSession::create([
            'table_id' => $newTableId,
            'customer_id' => $oldSession->customer_id,
            'start_time' => $oldSession->start_time, // Giữ start_time từ phiên cũ
            'status' => 'running',
            'transferred_from_session_id' => $oldSession->id,
            'transfer_reason' => $reason,
            'work_shift_id' => $oldSession->work_shift_id,
            'payment_method' => $oldSession->payment_method,
            'paused_at' => $oldSession->paused_at, // Giữ trạng thái pause nếu có
            'total_paused_seconds' => $oldSession->total_paused_seconds, // Giữ thời gian pause
        ]);

        // **Chuyển tất cả order items từ phiên cũ sang phiên mới**
        // Những món ăn/uống đã gọi sẽ vẫn được tính trên bàn mới
        $oldSession->orderItems()->update([
            'game_session_id' => $newSession->id,
        ]);

        // Cập nhật phiên cũ
        $oldSession->update([
            'status' => 'transferred',
        ]);

        return $newSession;
    }

    /**
     * Tạm dừng phiên chơi
     *
     * @param GameSession $session
     * @return bool
     */
    public function pauseSession(GameSession $session): bool
    {
        if ($session->status !== 'running') {
            throw new \RuntimeException('Chỉ có thể tạm dừng phiên đang chạy');
        }

        $session->pause();
        return true;
    }

    /**
     * Tiếp tục phiên chơi
     *
     * @param GameSession $session
     * @return bool
     */
    public function resumeSession(GameSession $session): bool
    {
        if ($session->status !== 'running') {
            throw new \RuntimeException('Chỉ có thể tiếp tục phiên đang chạy');
        }

        if (!$session->isPaused()) {
            throw new \RuntimeException('Phiên này không bị tạm dừng');
        }

        $session->resume();
        return true;
    }

    /**
     * Kết thúc phiên chơi
     *
     * @param GameSession $session
     * @return bool
     */
    public function endSession(GameSession $session): bool
    {
        if ($session->status !== 'running') {
            throw new \RuntimeException('Phiên này đã kết thúc rồi');
        }

        $session->update([
            'end_time' => now(),
            'status' => 'completed',
            'paused_at' => null, // Bỏ trạng thái tạm dừng khi kết thúc
        ]);

        return true;
    }

    /**
     * Lấy thông tin chi tiết về một phiên (bao gồm các phiên ghép vào)
     *
     * @param GameSession $session
     * @return array
     */
    public function getSessionDetails(GameSession $session): array
    {
        $allSessions = $session->getAllMergedSessions();
        $allOrderItems = $session->getAllOrderItems();

        return [
            'main_session' => $session,
            'merged_sessions' => $session->mergedSessions()->get(),
            'all_sessions' => $allSessions,
            'all_order_items' => $allOrderItems,
            'total_items_count' => $allOrderItems->count(),
            'total_money' => $session->getTotalMoneyIncludingMerged(),
        ];
    }

    /**
     * Lấy danh sách các phiên đang chạy
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRunningSessionsWithPauseInfo()
    {
        return GameSession::where('status', 'running')
            ->with(['bidaTable', 'customer'])
            ->get()
            ->map(function ($session) {
                return [
                    'id' => $session->id,
                    'table' => $session->bidaTable,
                    'customer' => $session->customer,
                    'start_time' => $session->start_time,
                    'is_paused' => $session->isPaused(),
                    'paused_since' => $session->paused_at,
                    'total_paused_seconds' => $session->getTotalPausedSeconds(),
                ];
            });
    }
}
