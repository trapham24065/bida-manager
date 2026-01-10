<?php

namespace App\Filament\Resources\Tables\Pages;

use App\Filament\Resources\Tables\TableResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTables extends ListRecords
{

    protected static string $resource = TableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Tạo bàn'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tất cả')->icon('heroicon-m-squares-2x2'),

            'bida' => Tab::make('Khu vực Bida')
                ->icon('heroicon-m-play-circle')
                // Lọc các bàn CÓ loại bàn thuộc nhóm 'bida'
                ->modifyQueryUsing(fn(Builder $query) => $query->whereHas('tableType', function ($q) {
                    $q->where('category', 'bida');
                })),

            'cafe' => Tab::make('Khu vực Cafe')
                ->icon('heroicon-o-trophy')
                // Lọc các bàn CÓ loại bàn thuộc nhóm 'cafe'
                ->modifyQueryUsing(fn(Builder $query) => $query->whereHas('tableType', function ($q) {
                    $q->where('category', 'cafe');
                })),
        ];
    }

}
