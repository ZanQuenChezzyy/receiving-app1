<?php

namespace App\Filament\Resources\DeliveryOrderReceiptResource\Pages;

use App\Filament\Resources\DeliveryOrderReceiptResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDeliveryOrderReceipt extends ViewRecord
{
    protected static string $resource = DeliveryOrderReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
