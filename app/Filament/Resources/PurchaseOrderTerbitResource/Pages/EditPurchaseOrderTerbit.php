<?php

namespace App\Filament\Resources\PurchaseOrderTerbitResource\Pages;

use App\Filament\Resources\PurchaseOrderTerbitResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseOrderTerbit extends EditRecord
{
    protected static string $resource = PurchaseOrderTerbitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
