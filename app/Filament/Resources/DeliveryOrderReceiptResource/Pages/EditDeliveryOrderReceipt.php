<?php

namespace App\Filament\Resources\DeliveryOrderReceiptResource\Pages;

use App\Filament\Resources\DeliveryOrderReceiptResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDeliveryOrderReceipt extends EditRecord
{
    protected static string $resource = DeliveryOrderReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
