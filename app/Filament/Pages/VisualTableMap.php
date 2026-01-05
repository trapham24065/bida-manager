<?php

namespace App\Filament\Pages;

use App\Models\Table;
use App\Models\Booking;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class VisualTableMap extends Page
{

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationLabel = 'Sơ đồ bàn (Map)';

    protected static ?string $title = 'Sơ đồ bố trí bàn Bida';

    protected string $view = 'filament.pages.visual-table-map';

    // Biến để lưu trạng thái bật/tắt chế độ sửa
    public $isEditMode = false;

    // Load danh sách bàn kèm thông tin trạng thái
    public function getViewData(): array
    {
        return [
            'tables' => Table::with([
                'currentSession',
                'bookings' => function ($q) {
                    // Lấy booking sắp tới trong 1 tiếng
                    $q->where('status', 'pending')
                        ->where('booking_time', '>=', now())
                        ->where('booking_time', '<=', now()->addHour());
                },
            ])->get(),
        ];
    }

    // Hàm lưu vị trí mới (Được gọi từ Javascript)
    public function updateTablePosition($id, $x, $y): void
    {
        Table::whereKey($id)->update([
            'position_x' => (int) $x,
            'position_y' => (int) $y,
        ]);
    }

    // Nút bật/tắt chế độ chỉnh sửa trên Header
    protected function getHeaderActions(): array
    {
        return [
            Action::make('toggleEdit')
                ->label(fn() => $this->isEditMode ? 'Lưu & Khóa sơ đồ' : 'Chỉnh sửa vị trí')
                ->icon(fn() => $this->isEditMode ? 'heroicon-o-check' : 'heroicon-o-pencil')
                ->action(fn() => $this->isEditMode = !$this->isEditMode),
        ];
    }
}
