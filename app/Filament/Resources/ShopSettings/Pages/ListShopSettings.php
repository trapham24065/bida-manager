<?php

namespace App\Filament\Resources\ShopSettings\Pages;

use App\Filament\Resources\ShopSettings\ShopSettingResource;
use App\Models\ShopSetting;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListShopSettings extends ListRecords
{

    protected static string $resource = ShopSettingResource::class;

    protected function getHeaderActions(): array
    {
        // LOGIC: Kiểm tra xem trong bảng đã có dữ liệu chưa?
        // Nếu đã có (exists) thì trả về mảng rỗng [] -> Tức là không hiện nút nào cả
        if (ShopSetting::exists()) {
            return [];
        }

        // Nếu chưa có (lần đầu tiên chạy) thì mới hiện nút Create
        return [
            CreateAction::make(),
        ];
    }

}
