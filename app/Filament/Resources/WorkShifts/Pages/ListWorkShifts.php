<?php

namespace App\Filament\Resources\WorkShifts\Pages;

use App\Filament\Resources\WorkShifts\WorkShiftResource;
use App\Models\WorkShift;
use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListWorkShifts extends ListRecords
{

    protected static string $resource = WorkShiftResource::class;

    protected function getHeaderActions(): array
    {
        // Kiểm tra xem User có ca nào đang mở không
        $currentShift = WorkShift::myCurrentShift();

        if ($currentShift) {
            // === NẾU ĐANG CÓ CA -> HIỆN NÚT CHỐT CA ===
            return [
                Actions\Action::make('close_shift')
                    ->label('🔒 CHỐT CA & BÀN GIAO')
                    ->color('danger')
                    ->modalHeading('Chốt ca làm việc')
                    ->modalDescription('Vui lòng đếm kỹ tiền trong két trước khi nhập.')
                    ->form([
                        // Hiển thị thông tin hệ thống tính toán (Read only)
                        TextInput::make('system_calc')
                            ->label('Hệ thống tính (Vốn + Thu Tiền Mặt)')
                            ->default(
                                fn() => number_format(
                                    $currentShift->initial_cash + $currentShift->gameSessions()->sum('total_money')
                                )
                            ) // Lưu ý: Ở đây mình tạm tính tổng, nếu bạn làm kỹ hơn thì lọc payment_method = cash
                            ->disabled(),

                        TextInput::make('reported_cash')
                            ->label('Tiền thực tế trong két')
                            ->numeric()
                            ->required()
                            ->suffix('VNĐ'),

                        Textarea::make('note')->label('Ghi chú (nếu lệch)'),
                    ])
                    ->action(function (array $data) use ($currentShift) {
                        // Lấy tất cả hóa đơn trong ca này
                        $sessions = $currentShift->gameSessions;

                        // 1. Tách riêng doanh thu
                        $cashSales = $sessions->where('payment_method', 'cash')->sum('total_money');
                        $transferSales = $sessions->where('payment_method', 'transfer')->sum('total_money');

                        // 2. Tính tiền lý thuyết trong két
                        // Két = Vốn + Tiền mặt thu được (Chuyển khoản ko vào két)
                        $theoretical = $currentShift->initial_cash + $cashSales;

                        $reported = $data['reported_cash'];
                        $diff = $reported - $theoretical;

                        // 3. Cập nhật đóng ca
                        $currentShift->update([
                            'end_time'             => now(),
                            'total_cash_money'     => $cashSales,       // Chỉ lưu tổng tiền mặt
                            'total_transfer_money' => $transferSales, // Chỉ lưu tổng chuyển khoản
                            'reported_cash'        => $reported,
                            'difference'           => $diff,
                            'note'                 => $data['note'],
                            'status'               => 'closed',
                        ]);

                        Notification::make()->title('Đã chốt ca!')->success()->send();
                    }),
            ];
        }

// === NẾU CHƯA CÓ CA -> HIỆN NÚT VÀO CA ===
        return [
            Actions\Action::make('start_shift')
                ->label('👋 VÀO CA LÀM VIỆC')
                ->color('success')
                ->icon('heroicon-o-play')
                ->modalHeading('Khai báo đầu ca')
                ->form([
                    TextInput::make('initial_cash')
                        ->label('Tiền đang có trong két (Vốn)')
                        ->numeric()
                        ->default(0)
                        ->required()
                        ->suffix('VNĐ'),
                ])
                ->action(function (array $data) {
                    WorkShift::create([
                        'user_id'      => auth()->id(),
                        'start_time'   => now(),
                        'initial_cash' => $data['initial_cash'],
                        'status'       => 'open',
                    ]);
                    Notification::make()->title('Chúc bạn một ca làm việc vui vẻ!')->success()->send();
                }),
        ];
    }

}
