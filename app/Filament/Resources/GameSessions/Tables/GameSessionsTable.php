<?php

namespace App\Filament\Resources\GameSessions\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;

class GameSessionsTable
{

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('Mã HD')
                    ->sortable()
                    ->searchable(),
                // Hiển thị tên bàn (thông qua quan hệ bidaTable)
                TextColumn::make('bidaTable.name')
                    ->label('Bàn / Khu vực')
                    ->badge() // Hiển thị dạng nhãn cho đẹp
                    ->color(fn($state) => $state === 'Mang về (Takeaway)' ? 'warning' : 'info')
                    ->icon(
                        fn($state) => $state === 'Mang về (Takeaway)' ? 'heroicon-m-shopping-bag'
                            : 'heroicon-m-table-cells'
                    )
                    ->sortable()
                    ->searchable(),

                TextColumn::make('start_time')
                    ->label('Bắt đầu')
                    ->dateTime('H:i d/m')
                    ->sortable(),

                TextColumn::make('end_time')
                    ->label('Kết thúc')
                    ->dateTime('H:i d/m')
                    ->sortable()
                    ->placeholder('Đang chơi...'),

                TextColumn::make('total_money')
                    ->label('Tổng tiền')
                    ->money('VND')
                    ->weight('bold')
                    ->summarize([
                        // Tính tổng doanh thu của trang hiện tại
                        Sum::make()->label('Tổng trang này'),
                    ]),

                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'running' => 'warning', // Màu vàng
                        'completed' => 'success', // Màu xanh
                        default => 'gray',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                // 1. Lọc theo Bàn (Chọn từ danh sách)
                SelectFilter::make('table_id')
                    ->label('Chọn bàn')
                    ->relationship('bidaTable', 'name'),

                // 2. Lọc theo Khoảng thời gian (Từ ngày... Đến ngày...)
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')->label('Từ ngày'),
                        DatePicker::make('created_until')->label('Đến ngày'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date) => $query->whereDate('start_time', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date) => $query->whereDate('start_time', '<=', $date),
                            );
                    }),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exports([
                        ExcelExport::make()
                            ->fromTable()
                            ->withFilename('Doanh_thu_'.date('d-m-Y'))
                            ->withColumns([
                                // Định nghĩa rõ từng cột muốn xuất ra Excel
                                Column::make('id')->heading('Mã Hóa Đơn'),

                                // Lấy tên bàn từ quan hệ
                                Column::make('bidaTable.name')->heading('Tên Bàn'),

                                Column::make('start_time')->heading('Giờ vào'),
                                Column::make('end_time')->heading('Giờ ra'),

                                // Xuất số tiền nguyên bản (không có chữ đ) để vào Excel còn cộng trừ được
                                Column::make('total_money')->heading('Tổng tiền'),

                                Column::make('status')
                                    ->heading('Trạng thái')
                                    ->formatStateUsing(fn($state) => match ($state) {
                                        'completed' => 'Đã thanh toán',
                                        'running' => 'Đang chơi',
                                        default => $state,
                                    }),
                            ]),
                    ])
                    ->label('Xuất Excel')
                    ->color('success'),
            ])
            ->actions([
                ViewAction::make()
                    ->label('Chi tiết')
                    ->modalHeading('Chi tiết hóa đơn')
                    ->color('info'), // Màu xanh dươn
            ]);
    }

}
