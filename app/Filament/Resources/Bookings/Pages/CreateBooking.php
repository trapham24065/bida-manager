<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Booking;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;

class CreateBooking extends CreateRecord
{

    protected static string $resource = BookingResource::class;

    protected function beforeCreate(): void
    {
        // Lấy dữ liệu từ form
        $data = $this->data;

        $tableId = $data['table_id'];
        $newStart = \Carbon\Carbon::parse($data['booking_time']);
        $newEnd = $newStart->copy()->addMinutes($data['duration_minutes']);

        // Tìm xem có booking nào trùng thời gian không
        // Logic: (StartA < EndB) AND (EndA > StartB)
        $conflict = Booking::where('table_id', $tableId)
            ->where('status', 'pending') // Chỉ check những đơn chưa hủy/chưa xong
            ->where(function ($query) use ($newStart, $newEnd) {
                $query->whereBetween('booking_time', [$newStart, $newEnd])
                    ->orWhereRaw(
                        "DATE_ADD(booking_time, INTERVAL duration_minutes MINUTE) BETWEEN ? AND ?",
                        [$newStart, $newEnd]
                    );
            })
            ->first();

        if ($conflict) {
            Notification::make()
                ->title('Trùng lịch đặt!')
                ->body("Bàn này đã có khách {$conflict->customer_name} đặt lúc ".$conflict->booking_time)
                ->danger()
                ->persistent() // Thông báo không tự tắt
                ->send();

            $this->halt(); // Dừng lại, không cho lưu
        }
    }

    protected function getRedirectUrl(): string
    {
        return self::getResource()::getUrl('index');
    }

}
