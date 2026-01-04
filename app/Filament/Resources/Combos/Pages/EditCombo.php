<?php

namespace App\Filament\Resources\Combos\Pages;

use App\Filament\Resources\Combos\ComboResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCombo extends EditRecord
{

    protected static string $resource = ComboResource::class;

    protected function getRedirectUrl(): string
    {
        return self::getResource()::getUrl('index');
    }

    protected array $comboItemsData = [];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load combo items để hiển thị trong form
        $comboItems = $this->record->comboItems->map(function ($item) {
            return [
                'product_id' => $item->id,
                'quantity'   => $item->pivot->quantity,
            ];
        })->toArray();

        $data['combo_items'] = $comboItems;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Lưu combo_items vào biến tạm, xóa khỏi data
        $this->comboItemsData = $data['combo_items'] ?? [];
        unset($data['combo_items']);

        return $data;
    }

    protected function afterSave(): void
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

}
