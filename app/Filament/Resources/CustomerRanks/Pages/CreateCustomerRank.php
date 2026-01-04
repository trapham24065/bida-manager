<?php

namespace App\Filament\Resources\CustomerRanks\Pages;

use App\Filament\Resources\CustomerRanks\CustomerRankResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerRank extends CreateRecord
{
    protected static string $resource = CustomerRankResource::class;
}
