<?php

namespace App\Filament\Widgets;

use App\Models\GameSession;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class RevenueStatsOverview extends StatsOverviewWidget
{

    protected static ?int $sort = 1;

    protected function getColumns(): int
    {
        return 2;
    }

    protected function getStats(): array
    {
        // 2. Doanh thu TUẦN NÀY
        $weekRevenue = GameSession::where('status', 'completed')
            ->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->sum('total_money');

        // So sánh với tuần trước (để hiện mũi tên tăng/giảm)
        $lastWeekRevenue = GameSession::where('status', 'completed')
            ->whereBetween('created_at', [
                Carbon::now()->subWeek()->startOfWeek(),
                Carbon::now()->subWeek()->endOfWeek(),
            ])
            ->sum('total_money');

        // 3. Doanh thu THÁNG NÀY
        $monthRevenue = GameSession::where('status', 'completed')
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('total_money');

        return [
            Stat::make('Doanh thu Tuần này', number_format($weekRevenue).' đ')
                ->description($weekRevenue >= $lastWeekRevenue ? 'Tăng so với tuần trước' : 'Giảm so với tuần trước')
                ->descriptionIcon(
                    $weekRevenue >= $lastWeekRevenue ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down'
                )
                ->color($weekRevenue >= $lastWeekRevenue ? 'success' : 'danger')
                ->chart([7, 2, 10, 3, 15, 4, 17]), // Chart giả lập cho đẹp (hoặc query data thật nếu muốn)

            Stat::make('Doanh thu Tháng '.Carbon::now()->month, number_format($monthRevenue).' đ')
                ->description('Tháng hiện tại')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('primary'),
        ];
    }

}
