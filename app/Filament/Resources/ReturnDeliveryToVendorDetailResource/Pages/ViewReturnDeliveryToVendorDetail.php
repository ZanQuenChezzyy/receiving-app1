<?php

namespace App\Filament\Resources\ReturnDeliveryToVendorDetailResource\Pages;

use App\Filament\Resources\ReturnDeliveryToVendorDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewReturnDeliveryToVendorDetail extends ViewRecord
{
    protected static string $resource = ReturnDeliveryToVendorDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
