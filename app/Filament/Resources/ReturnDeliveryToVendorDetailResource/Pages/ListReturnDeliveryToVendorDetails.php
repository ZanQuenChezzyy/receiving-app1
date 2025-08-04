<?php

namespace App\Filament\Resources\ReturnDeliveryToVendorDetailResource\Pages;

use App\Filament\Resources\ReturnDeliveryToVendorDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReturnDeliveryToVendorDetails extends ListRecords
{
    protected static string $resource = ReturnDeliveryToVendorDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
