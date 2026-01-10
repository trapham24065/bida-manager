<?php

namespace App\Filament\Widgets;

use App\Models\GameSession;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class MonthlyRevenueChart extends ChartWidget
{

    protected ?string $heading = 'Doanh thu theo Tháng (Năm nay)';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        // Lấy dữ liệu 12 tháng của năm nay
        $data = Trend::query(
            GameSession::where('status', 'completed') // <--- Truyền Query Builder vào đây
        )
            ->between(
                start: now()->startOfYear(),
                end: now()->endOfYear(),
            )
            ->perMonth()
            ->sum('total_money');

        return [
            'datasets' => [
                [
                    'label'           => 'Doanh thu (VNĐ)',
                    'data'            => $data->map(fn(TrendValue $value) => $value->aggregate),
                    'backgroundColor' => '#3b82f6', // Màu xanh dương
                    'borderColor'     => '#2563eb',
                ],
            ],
            'labels'   => $data->map(fn(TrendValue $value) => "Tháng ".\Carbon\Carbon::parse($value->date)->month),
        ];
    }

    protected function getType(): string
    {
        return 'bar'; // Dạng biểu đồ cột (Bar chart)
    }

}
