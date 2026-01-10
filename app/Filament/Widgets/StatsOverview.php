<?php

namespace App\Filament\Widgets;

use App\Models\GameSession;
use App\Models\OrderItem;

// Nhớ import Model này để lấy giá vốn
use App\Models\Table;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{

    // Sắp xếp widget này nằm trên cùng
    protected static ?int $sort = 1;

    // Tự động refresh dữ liệu sau mỗi 15 giây
    protected ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        // 1. Tính DOANH THU hôm nay (Tổng tiền thu về)
        $revenueToday = GameSession::whereDate('end_time', Carbon::today())->sum('total_money');

        // 2. Tính GIÁ VỐN hàng bán hôm nay
        // Lấy tất cả các món đã bán hôm nay nhân với giá vốn lúc bán
        $costToday = OrderItem::whereDate('created_at', Carbon::today())
            ->get()
            ->sum(function ($item) {
                return $item->quantity * $item->cost;
            });
        // 3. Tính LÃI RÒNG (Doanh thu - Giá vốn)
        // Lưu ý: Tiền giờ được coi là lãi 100% (chi phí điện coi như chi phí cố định)
        $profitToday = $revenueToday - $costToday;

        // 4. Số bàn đang hoạt động
        $activeTables = Table::whereHas('gameSessions', function ($query) {
            $query->where('status', 'running');
        })->count();
        $totalTables = Table::count();

        return [
            // Ô 1: DOANH THU (Màu xanh dương)
            Stat::make('Doanh thu hôm nay', number_format($revenueToday).' đ')
                ->description('Tổng thu')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info')
                ->chart([7, 3, 10, 3, 15, 4, 10]),

            // Ô 2: LÃI RÒNG (Quan trọng nhất - Màu xanh lá)
            Stat::make('Lãi ròng hôm nay', number_format($profitToday).' đ')
                ->description('Sau khi trừ vốn hàng bán')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success') // Màu xanh lá uy tín
                ->chart([2, 10, 3, 12, 1, 10, 15]),

            // Ô 3: BÀN ĐANG HOẠT ĐỘNG (Màu vàng/xám)
            Stat::make('Bàn đang hoạt động', $activeTables.' / '.$totalTables)
                ->description(
                    'Tỉ lệ lấp đầy: '.($totalTables > 0 ? round(($activeTables / $totalTables) * 100) : 0).'%'
                )
                ->descriptionIcon('heroicon-m-users')
                ->color($activeTables > 0 ? 'warning' : 'gray'),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()->role === 'admin';
    }

}
