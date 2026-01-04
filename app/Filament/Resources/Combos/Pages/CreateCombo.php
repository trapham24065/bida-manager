<?php

namespace App\Filament\Resources\Combos\Pages;

use App\Filament\Resources\Combos\ComboResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCombo extends CreateRecord
{

    protected static string $resource = ComboResource::class;

    protected array $comboItemsData = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Lưu combo_items vào biến tạm, xóa khỏi data để không lưu vào products table
        $this->comboItemsData = $data['combo_items'] ?? [];
        unset($data['combo_items']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $combo = $this->record;

        if (empty($this->comboItemsData)) {
            return;
        }

        $syncData = [];

        foreach ($this->comboItemsData as $item) {
            $syncData[$item['product_id']] = [
                'quantity' => $item['quantity'],
            ];
        }

        $combo->comboItems()->sync($syncData);
    }

    protected function getRedirectUrl(): string
    {
        return self::getResource()::getUrl('index');
    }

}
