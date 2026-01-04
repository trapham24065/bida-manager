<?php

namespace App\Filament\Resources\Bookings\Tables;

use App\Models\Booking;
use App\Models\GameSession;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BookingsTable
{

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('booking_time')
                    ->label('Giờ hẹn')
                    ->dateTime('H:i d/m/Y')
                    ->sortable()
                    ->badge() // Bôi màu cho nổi bật giờ giấc
                    ->color('warning'),

                TextColumn::make('bidaTable.name')
                    ->label('Bàn')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customer_name')
                    ->label('Khách hàng')
                    ->searchable()
                    ->description(fn(Booking $record) => $record->phone),

                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',  // Vàng: Chờ
                        'checked_in' => 'success', // Xanh: Đã vào
                        'cancelled' => 'danger',   // Đỏ: Hủy
                    }),
            ])
            ->defaultSort('booking_time', 'asc')
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('check_in')
                    ->label('Khách đã đến')
                    ->icon('heroicon-o-play-circle')
                    ->color('success')
                    ->visible(fn(Booking $record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (Booking $record) {
                        // 1. Kiểm tra xem bàn có đang bận không?
                        $table = $record->bidaTable;
                        if ($table->hasRunningSession()) {
                            Notification::make()
                                ->title('Không thể mở bàn!')
                                ->body("Bàn {$table->name} đang có khách khác chơi. Vui lòng thanh toán bàn đó trước.")
                                ->danger()
                                ->send();
                            return;
                        }

                        // 2. Tạo phiên chơi mới (GameSession)
                        GameSession::create([
                            'table_id'   => $table->id,
                            'start_time' => now(), // Tính giờ từ lúc khách đến thực tế
                            'status'     => 'running',
                        ]);

                        // 3. Cập nhật trạng thái Booking -> Đã vào
                        $record->update(['status' => 'checked_in']);

                        Notification::make()
                            ->title('Đã mở bàn thành công!')
                            ->body('Hệ thống đã bắt đầu tính tiền.')
                            ->success()
                            ->send();

                        // Chuyển hướng về trang Dashboard bàn
                        return redirect()->to('/admin/tables');
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

}
