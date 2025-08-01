<?php

namespace App\Filament\Resources\ReturnDeliveryToVendorDetailResource\Pages;

use App\Filament\Resources\ReturnDeliveryToVendorDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReturnDeliveryToVendorDetail extends EditRecord
{
    protected static string $resource = ReturnDeliveryToVendorDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
