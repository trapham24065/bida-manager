<?php

namespace App\Filament\Widgets;

use App\Models\GameSession;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class RevenueChart extends ChartWidget
{

    protected ?string $heading = 'Biểu đồ doanh thu (7 ngày qua)';

    // Sắp xếp thứ tự: Để biểu đồ nằm dưới các thẻ thống kê
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        // Sử dụng thư viện Trend để lấy dữ liệu 7 ngày gần nhất
        $data = Trend::model(GameSession::class)
            ->between(
                start: now()->subDays(6), // Từ 6 ngày trước
                end: now(),               // Đến hôm nay
            )
            ->perDay() // Tính theo ngày
            ->sum('total_money'); // Cộng tổng tiền cột total_money

        return [
            'datasets' => [
                [
                    'label'           => 'Doanh thu (VNĐ)',
                    'data'            => $data->map(fn(TrendValue $value) => $value->aggregate),
                    'backgroundColor' => '#36A2EB', // Màu nền cột
                    'borderColor'     => '#9BD0F5',
                ],
            ],
            'labels'   => $data->map(fn(TrendValue $value) => $value->date),
        ];
    }

    protected function getType(): string
    {
        return 'line'; // Chọn loại biểu đồ: 'line' (đường) hoặc 'bar' (cột)
    }

    public static function canView(): bool
    {
        return auth()->user()->role === 'admin';
    }

}
