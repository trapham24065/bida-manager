<?php

namespace App\Filament\Resources\ShopSettings\Pages;

use App\Filament\Resources\ShopSettings\ShopSettingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditShopSetting extends EditRecord
{

    protected static string $resource = ShopSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return self::getResource()::getUrl('index');
    }

}
