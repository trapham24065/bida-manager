<?php

namespace App\Filament\Widgets;

use App\Models\OrderItem;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ProductStatsChart extends ChartWidget
{

    protected ?string $heading = 'Top 5 Món Bán Chạy Nhất';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
// Lấy danh sách 5 món có tổng số lượng bán cao nhất
        $data = OrderItem::select('product_id', DB::raw('SUM(quantity) as total_qty'))
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->with('product') // Load quan hệ để lấy tên
            ->get();

        return [
            'datasets' => [
                [
                    'label'           => 'Số lượng đã bán',
                    'data'            => $data->pluck('total_qty'), // Mảng số lượng
                    'backgroundColor' => [
                        '#FF6384',
                        '#36A2EB',
                        '#FFCE56',
                        '#4BC0C0',
                        '#9966FF',
                    ],
                ],
            ],
            'labels'   => $data->pluck('product.name'), // Mảng tên món
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

}
