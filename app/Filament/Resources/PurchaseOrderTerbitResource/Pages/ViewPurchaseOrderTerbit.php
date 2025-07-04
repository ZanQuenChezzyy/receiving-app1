<?php

namespace App\Filament\Resources\PurchaseOrderTerbitResource\Pages;

use App\Filament\Resources\PurchaseOrderTerbitResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchaseOrderTerbit extends ViewRecord
{
    protected static string $resource = PurchaseOrderTerbitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
