<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Notifications\Notification;

class UpcomingBookings extends BaseWidget
{

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = '🔔 KHÁCH SẮP ĐẾN';

    protected static ?string $pollingInterval = '30s'; // Quét mỗi 30 giây

    public function table(Table $table): Table
    {
        // ============================================================
        // 1. LOGIC TỰ ĐỘNG HỦY & BÁO HỦY (Quá 10 phút)
        // ============================================================
        $lateBookings = Booking::where('status', 'pending')
            ->where('booking_time', '<', now()->subMinutes(10))
            ->get();

        foreach ($lateBookings as $booking) {
            // Đổi trạng thái hủy
            $booking->update(['status' => 'cancelled']);

            // Bắn thông báo ĐỎ
            Notification::make()
                ->title('Đã hủy lịch tự động')
                ->body("Khách hàng {$booking->customer_name} đã trễ quá 10 phút.")
                ->danger() // Màu đỏ
                ->duration(10000) // Hiện trong 10 giây
                ->send();
        }

        // ============================================================
        // 2. LOGIC NHẮC NHỞ SẮP ĐẾN (Trước 15 phút)
        // ============================================================
        // Tìm các đơn: Chưa xong + Sắp đến trong 15p nữa + Chưa từng thông báo
        $upcomingBookings = Booking::where('status', 'pending')
            ->whereBetween('booking_time', [now(), now()->addMinutes(15)])
            ->whereNull('reminded_at') // Quan trọng: Chỉ lấy đơn chưa báo
            ->get();

        foreach ($upcomingBookings as $booking) {
            // Đánh dấu là đã báo (để 30s sau không báo lại nữa)
            $booking->update(['reminded_at' => now()]);

            // Bắn thông báo VÀNG
            Notification::make()
                ->title('Khách sắp đến!')
                ->body(
                    "Khách {$booking->customer_name} đặt bàn {$booking->bidaTable->name} sẽ đến lúc {$booking->booking_time->format('H:i')}."
                )
                ->warning() // Màu vàng
                ->actions([
                    // Thêm nút nhận bàn nhanh ngay trên thông báo
                    Action::make('check_in')
                        ->label('Nhận bàn ngay')
                        ->button()
                        ->url('/admin/bookings'),
                ])
                ->persistent() // Không tự tắt, phải bấm tắt mới mất (để nhân viên chú ý)
                ->send();
        }

        // ============================================================
        // 3. HIỂN THỊ BẢNG (Giữ nguyên logic cũ)
        // ============================================================
        return $table
            ->query(
                Booking::query()
                    ->where('status', 'pending')
                    ->whereBetween('booking_time', [now()->subMinutes(10), now()->addHour()])
            )
            ->columns([
                Tables\Columns\TextColumn::make('booking_time')
                    ->label('Giờ hẹn')
                    ->time('H:i')
                    ->description(fn(Booking $record) => $record->booking_time->diffForHumans())
                    ->badge()
                    ->color(fn($record) => $record->booking_time->lessThan(now()) ? 'danger' : 'warning'),

                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Khách hàng')
                    ->description(fn($record) => $record->phone)
                    ->searchable(),

                Tables\Columns\TextColumn::make('bidaTable.name')->label('Bàn')->weight('bold'),

                Tables\Columns\TextColumn::make('status_check')
                    ->label('Tình trạng bàn')
                    ->state(fn($record) => $record->bidaTable->hasRunningSession() ? 'ĐANG CÓ KHÁCH!' : 'Bàn trống')
                    ->badge()
                    ->color(fn($state) => $state === 'ĐANG CÓ KHÁCH!' ? 'danger' : 'success'),
            ])
            ->actions([
                Action::make('check_in')
                    ->label('Nhận')
                    ->button()
                    ->action(function (Booking $record) {
                        \App\Models\GameSession::create([
                            'table_id'   => $record->table_id,
                            'start_time' => now(),
                            'status'     => 'running',
                        ]);
                        $record->update(['status' => 'checked_in']);
                        return redirect()->to('/admin/tables');
                    }),
            ]);
    }

}
