<?php

namespace App\Filament\Resources\CustomerRanks\Pages;

use App\Filament\Resources\CustomerRanks\CustomerRankResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomerRanks extends ListRecords
{

    protected static string $resource = CustomerRankResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tạo xếp hạng'),
        ];
    }

}
