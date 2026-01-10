<?php

namespace App\Filament\Resources\TableTypes\Pages;

use App\Filament\Resources\TableTypes\TableTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTableTypes extends ManageRecords
{

    protected static string $resource = TableTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Tạo loại bàn'),
        ];
    }

}
