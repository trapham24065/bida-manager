<?php

namespace App\Filament\Resources\GameSessions;

use App\Filament\Resources\GameSessions\Pages\ListGameSessions;
use App\Filament\Resources\GameSessions\Schemas\GameSessionForm;
use App\Filament\Resources\GameSessions\Tables\GameSessionsTable;
use App\Models\GameSession;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class GameSessionResource extends Resource
{

    protected static ?string $model = GameSession::class;

    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Lịch sử Chơi';

    protected static ?string $pluralModelLabel = 'Lịch sử Chơi';

    protected static string|null|\UnitEnum $navigationGroup = 'Hệ thống'; // Gom nhóm cho gọn

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return GameSessionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GameSessionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGameSessions::route('/'),
        ];
    }

// Chặn xóa lịch sử
    public static function canDelete($record): bool
    {
        return auth()->user()->role === 'admin';
    }

    // Chặn xóa nhiều dòng cùng lúc (Bulk Delete)
    public static function canDeleteAny(): bool
    {
        return auth()->user()->role === 'admin';
    }

}
