<?php
/**
 * @project bida-manager
 * @author  M397
 * @email m397.dev@gmail.com
 * @date    1/6/2026
 * @time    2:46 PM
 */

namespace App\Services;

use App\Models\Booking;
use App\Models\GameSession;
use App\Models\Table;

class TableService
{

    /**
     * Kiểm tra xem có bị trùng lịch đặt không?
     * Trả về: null (nếu OK) hoặc String (thông báo lỗi)
     */
    public function checkAvailability(Table $table): ?string
    {
        $booking = Booking::where('table_id', $table->id)
            ->where('status', 'pending')
            ->whereBetween('booking_time', [now()->subMinutes(10), now()->addMinutes(60)])
            ->first();

        if ($booking) {
            $time = $booking->booking_time->format('H:i');
            return $booking->booking_time->lessThan(now())
                ? "Khách đặt lúc {$time} đang trễ. Vui lòng check-in lịch đặt!"
                : "Có khách đặt lúc {$time}. Không thể nhận khách vãng lai!";
        }
        return null;
    }

    public function startSession(Table $table): void
    {
        GameSession::create([
            'table_id'   => $table->id,
            'start_time' => now(),
            'status'     => 'running',
        ]);
    }

}
