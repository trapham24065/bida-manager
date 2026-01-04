<?php

namespace App\Filament\Resources\ShopSettings\Pages;

use App\Filament\Resources\ShopSettings\ShopSettingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateShopSetting extends CreateRecord
{

    protected static string $resource = ShopSettingResource::class;

    protected function getRedirectUrl(): string
    {
        return self::getResource()::getUrl('index');
    }

}
