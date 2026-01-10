<?php

namespace App\Filament\Resources\ActivityLogs;

use App\Filament\Resources\ActivityLogs\Pages\ManageActivityLogs;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

class ActivityLogResource extends Resource
{

    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Nhật ký Chỉnh sửa';

    protected static ?string $pluralModelLabel = 'Chỉnh sửa';

    protected static string|null|\UnitEnum $navigationGroup = 'Hệ thống';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // 1. NGƯỜI THỰC HIỆN
                TextColumn::make('causer.name')
                    ->label('Người thực hiện')
                    ->searchable(),

                // 2. HÀNH ĐỘNG (Tạo, Sửa, Xóa)
                TextColumn::make('event')
                    ->label('Hành động')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'created' => 'success', // Tạo mới màu xanh lá
                        'updated' => 'warning', // Sửa màu vàng
                        'deleted' => 'danger',  // Xóa màu đỏ
                        default => 'gray',
                    }),

                // 3. ĐỐI TƯỢNG BỊ TÁC ĐỘNG
                TextColumn::make('subject_type')
                    ->label('Đối tượng')
                    ->formatStateUsing(function ($state, $record) {
                        // Rút gọn tên Model cho đẹp (VD: App\Models\Product -> Product)
                        $modelName = class_basename($state);
                        // Kèm theo ID của đối tượng
                        return $modelName.' #'.$record->subject_id;
                    }),

                // 4. CHI TIẾT THAY ĐỔI (Cái này hay nhất)
                TextColumn::make('properties')
                    ->label('Chi tiết thay đổi')
                    ->limit(50) // Chỉ hiện ngắn gọn
                    ->formatStateUsing(function ($state) {
                        if (empty($state)) {
                            return null;
                        }

                        // Logic hiển thị: Cũ -> Mới
                        $changes = [];
                        if (isset($state['attributes'])) {
                            foreach ($state['attributes'] as $key => $newValue) {
                                // Bỏ qua mấy cột thời gian
                                if (in_array($key, ['updated_at', 'created_at'])) {
                                    continue;
                                }

                                $oldValue = $state['old'][$key] ?? '...';
                                $changes[] = "$key: $oldValue -> $newValue";
                            }
                        }
                        return implode(' | ', $changes);
                    })
                    ->wrap(), // Xuống dòng nếu dài quá

                TextColumn::make('created_at')
                    ->label('Thời gian')
                    ->dateTime('H:i d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageActivityLogs::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->role === 'admin';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

}
