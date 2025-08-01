<?php

namespace App\Filament\Resources\ReturnDeliveryToVendorResource\Pages;

use App\Filament\Resources\ReturnDeliveryToVendorResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewReturnDeliveryToVendor extends ViewRecord
{
    protected static string $resource = ReturnDeliveryToVendorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
