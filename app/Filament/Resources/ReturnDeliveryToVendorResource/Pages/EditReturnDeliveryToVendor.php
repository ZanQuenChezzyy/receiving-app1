<?php

namespace App\Filament\Resources\ReturnDeliveryToVendorResource\Pages;

use App\Filament\Resources\ReturnDeliveryToVendorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReturnDeliveryToVendor extends EditRecord
{
    protected static string $resource = ReturnDeliveryToVendorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
