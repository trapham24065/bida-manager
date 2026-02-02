<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Product;
use App\Models\StockInput;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProductsTable
{

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('Ảnh')
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl(url('/images/placeholder.png')),
                TextColumn::make('category.name')
                    ->label('Nhóm')
                    ->sortable()
                    ->badge(),
                TextColumn::make('name')
                    ->label('Tên món')
                    ->searchable()
                    ->weight('bold'), // In đậm tên cho đẹp

                TextColumn::make('price')
                    ->label('Giá bán')
                    ->money('VND'),

                TextColumn::make('cost_price') // Nên hiện thêm cột này để Admin soi lãi
                    ->label('Giá vốn')
                    ->money('VND')
                    ->toggleable(isToggledHiddenByDefault: true), // Mặc định ẩn, muốn xem thì bật

                TextColumn::make('stock')
                    ->label('Tồn kho')
                    ->badge()
                    ->formatStateUsing(function (Product $record) {
                        // Sản phẩm pha chế: hiển thị tồn kho ảo (tính từ nguyên liệu)
                        if ($record->is_recipe) {
                            $availableStock = $record->getAvailableStock();
                            return $availableStock . ' (pha chế)';
                        }
                        return $record->stock;
                    })
                    ->color(function (Product $record): string {
                        $stock = $record->is_recipe ? $record->getAvailableStock() : $record->stock;
                        return match (true) {
                            $stock <= 10 => 'danger',
                            $stock <= 20 => 'warning',
                            default => 'success',
                        };
                    }),

                IconColumn::make('is_recipe')
                    ->boolean()
                    ->label('Pha chế')
                    ->trueIcon('heroicon-o-beaker')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('info')
                    ->falseColor('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_active')->boolean()->label('Mở bán'),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->label('Lọc theo nhóm'),
            ])
            ->recordActions([
                EditAction::make(),

                // Nút nhập hàng - chỉ hiện cho sản phẩm thường (không phải pha chế)
                Action::make('import_stock')
                    ->label('Nhập hàng')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->visible(fn(Product $record) => auth()->user()->role === 'admin' && !$record->is_recipe)
                    ->form([
                        TextInput::make('quantity')
                            ->label('Số lượng nhập thêm')
                            ->numeric()
                            ->required() // Bắt buộc
                            ->minValue(1)
                            ->default(10),

                        TextInput::make('import_price')
                            ->label('Giá nhập lần này')
                            ->numeric()
                            ->required() // <--- BẮT BUỘC NHẬP GIÁ ĐỂ TÍNH TRUNG BÌNH
                            ->suffix('VNĐ')
                            ->placeholder('VD: 8000')
                            ->helperText('Nhập giá gốc mua vào để tính lại giá vốn trung bình.'),

                        Textarea::make('note') // Đổi sang Textarea nhập cho thoải mái
                            ->label('Ghi chú')
                            ->placeholder('VD: Nhập từ đại lý Bia Sài Gòn...'),
                    ])
                    ->action(function (Product $record, array $data) {
                        // 1. Lấy dữ liệu cũ
                        $oldStock = $record->stock;
                        $oldCost = $record->cost_price ?? 0; // Nếu chưa có giá vốn thì là 0

                        $importQty = (int)$data['quantity'];
                        $importPrice = (float)$data['import_price'];

                        // 2. Tính Giá vốn trung bình (Weighted Average Cost)
                        // Công thức: (Tổng tiền cũ + Tổng tiền mới) / Tổng số lượng
                        $totalOldValue = $oldStock * $oldCost;
                        $totalNewValue = $importQty * $importPrice;
                        $totalStock = $oldStock + $importQty;

                        if ($totalStock > 0) {
                            $newCost = ($totalOldValue + $totalNewValue) / $totalStock;
                        } else {
                            $newCost = $importPrice;
                        }

                        // 3. Lưu lịch sử (StockInput) - QUAN TRỌNG
                        // Giả sử Model StockInput của bạn map với bảng stock_histories mình đã chỉ
                        StockInput::create([
                            'product_id'   => $record->id,
                            'user_id'      => auth()->id(), // <--- LƯU NGƯỜI NHẬP
                            'quantity'     => $importQty,
                            'old_stock'    => $oldStock,    // <--- LƯU TỒN CŨ
                            'new_stock'    => $totalStock,  // <--- LƯU TỒN MỚI
                            'import_price' => $importPrice, // Giá nhập của đợt này
                            'note'         => $data['note'],
                        ]);

                        // 4. Cập nhật Sản phẩm
                        $record->update([
                            'stock'      => $totalStock,
                            'cost_price' => round($newCost), // Làm tròn giá vốn cho đẹp
                        ]);

                        Notification::make()
                            ->title('Nhập hàng thành công!')
                            ->body("Đã cộng {$importQty} vào kho. Giá vốn mới: " . number_format($newCost) . " đ")
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
