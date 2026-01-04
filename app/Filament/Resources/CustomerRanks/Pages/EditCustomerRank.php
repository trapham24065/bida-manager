<?php

namespace App\Filament\Resources\CustomerRanks\Pages;

use App\Filament\Resources\CustomerRanks\CustomerRankResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCustomerRank extends EditRecord
{
    protected static string $resource = CustomerRankResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
